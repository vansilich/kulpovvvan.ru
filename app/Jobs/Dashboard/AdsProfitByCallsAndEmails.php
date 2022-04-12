<?php

namespace App\Jobs\Dashboard;

use App\Helpers\Api\Analytics;
use App\Helpers\Api\YDirect;
use App\Helpers\Csv;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Handlers\Dashboard\Comagic as Comagic_Handler;
use App\Handlers\Dashboard\OneC as OneC_Handler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdsProfitByCallsAndEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $execution_id;

    private array $yandexDirectBannerIds = [];
    private array $googleAdwordsBannerIds = [];
    private array $oneC_invoicesIds = [];

    public function __construct(
        private string $dateStart,
        private string $dateEnd
    ){
        $this->execution_id = Str::uuid();
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function handle(
        Comagic_Handler $comagic,
        OneC_Handler $oneC
    ){

        $callsWithSessions = $comagic->callsWithSessionAndAdInfo( $this->dateStart, $this->dateEnd );
        $phoneOrders = $oneC->phoneOrders( $this->dateStart, $this->dateEnd );
        $callsWithSessions = $this->compareOneCAndComagicCalls( $callsWithSessions, $phoneOrders );
//        $this->saveCallsStatistic( $callsWithSessions );

        $this->saveAdsIds($callsWithSessions);

        $invoiceIds = $this->getInvoicesIds($callsWithSessions);
        $invoiceInfo = $oneC->getInvoiceInfo( $invoiceIds );

        $directBannerInfo = ( new YDirect() )->adsReport($this->dateStart, $this->dateEnd, $this->yandexDirectBannerIds);
        dd($directBannerInfo);
        $adwordsBannersInfo =  ( new Analytics() )->adsReport( "2022-02-01", "2022-03-31", $this->googleAdwordsBannerIds);
        $this->saveAdsStatistic($directBannerInfo, $adwordsBannersInfo);
        dd($adwordsBannersInfo);
    }

    private function saveCallsStatistic( array $callsWithSessions )
    {
        $csv = new Csv( storage_path(sprintf('app/public/jobs/Dashboard/AdsProfitByCallsAndEmails/%s_calls.csv', $this->execution_id)) );
        $csv->openStream();

        $csv->insertRow([ 'источник', 'компания', '№ объявления', 'номер телефона', 'время начала звонка' ]);
        foreach ($callsWithSessions as $call){
            $ad_data = $call->integrated_campaign_data;

            $csv->insertRow([
                $ad_data->adv_system,
                $ad_data->ext_campaign_name,
                $ad_data->ext_campaign_id,
                $call->contact_phone_number,
                $call->start_time,
            ]);
        }

        $csv->closeStream();
    }

    private function saveInvoicesStatistic(): void
    {

    }

    private function saveAdsStatistic( array $directBannerInfo, array $adwordsBannersInfo ): void
    {
        $csv = new Csv( storage_path(sprintf('app/public/jobs/Dashboard/AdsProfitByCallsAndEmails/%s_ad s.csv', $this->execution_id)) );
        $csv->openStream();

        $csv->insertRow([ 'источник', 'компания', '№ объявления', 'показы', 'звонки', 'клики', 'CTR', '' ]);
        foreach ($directBannerInfo as $adContainer){
            foreach ($adContainer as $adStats){

                $csv->insertRow([
                    'yandex.direct',
                    $adStats['AdId'],
                    $adStats['Date'],
                    $adStats['Ctr'],
                    $adStats['Impressions'],
                    $adStats['Clicks'],
                    $adStats['Cost'],
                ]);
            }
        }

        $csv->closeStream();
    }

    /**
     * Assign 1C record of call to Comagic objects
     */
    private function compareOneCAndComagicCalls( array $comagic_calls, array $oneC_calls ): array
    {

        $calls = [];
        foreach ($comagic_calls as $comagic_call){
            if ( isset($oneC_calls[ $comagic_call->contact_phone_number ]) ) {

                $oneC_call_data = $oneC_calls[ $comagic_call->contact_phone_number ];

                //Remove 1C records, which earlier than Comagic call start date
                foreach ($oneC_call_data as $oneC_key => $oneC_data){
                    if ( (new Carbon($oneC_data->call_date))->timestamp <= (new Carbon($comagic_call->start_time))->timestamp ) {
                        unset( $oneC_call_data[$oneC_key] );
                    }
                }

                if ( !empty($oneC_call_data) ) {
                    $comagic_call->oneC_data = $oneC_call_data;
                    $calls[] = $comagic_call;
                }
            }
        }

        return $calls;
    }

    /**
     * Save Yandex.Direct banner ids to $this->yandexDirectBannerIds and Google.Adwords ids to $this->googleAdwordsBannerIds
     */
    public function saveAdsIds(array $comagicCalls): void
    {
        foreach ($comagicCalls as $call){

            switch ($call->integrated_campaign_data->adv_system) {
                case 'yandex.direct':
                    $this->yandexDirectBannerIds[] = $call->integrated_campaign_data->current_banner_id;
                    break;
                case 'google.adwords':
                    $this->googleAdwordsBannerIds[] = $call->integrated_campaign_data->current_banner_id;
            }
        }

        $this->yandexDirectBannerIds = array_unique($this->yandexDirectBannerIds);
        $this->googleAdwordsBannerIds = array_unique($this->googleAdwordsBannerIds);
    }

    public function getInvoicesIds(array $comagicCalls): array
    {

        $invoicesIds = [];
        foreach ($comagicCalls as $call) {
            foreach ($call->oneC_data as $invoice) {

                if ($invoice->order_id !== null) {
                    $invoicesIds[] = $invoice->order_id;
                }
            }
        }

        return array_unique($invoicesIds);
    }

}
