<?php

namespace App\Jobs\Dashboard;

use App\Handlers\Dashboard\Comagic as Comagic_Handler;
use App\Helpers\Api\Analytics;
use App\Helpers\Api\OneC as OneC_API;
use App\Helpers\Api\YDirect;
use App\Helpers\Csv;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
        OneC_API $oneC,
        YDirect $direct,
        Analytics $analytics,
    ){
        $callsWithSessions = $comagic->callsWithSessionAndAdInfo( $this->dateStart, $this->dateEnd );
        $phoneOrders = $oneC->phoneOrders( $this->dateStart, $this->dateEnd );
        $callsWithSessions = $this->compareOneCAndComagicCalls( $callsWithSessions, $phoneOrders );
        $this->saveCallsStatistic( $callsWithSessions );

        $invoiceIds = $this->getInvoicesIds($callsWithSessions);
        $invoiceInfo = $oneC->getInvoiceInfo( $invoiceIds );
        //trim headers
        array_shift($invoiceInfo);

        $this->saveInvoicesStatistic( $invoiceInfo );

        $this->saveAdsIds($callsWithSessions);

        $directBannerInfo = $direct->adsReportGroupByDay($this->dateStart, $this->dateEnd, $this->yandexDirectBannerIds);
        $directBannerInfo = $this->prepareYandexDirectReport( $directBannerInfo );

        $adwordsBannersInfo =  $analytics->adsReportGroupByDay( $this->dateStart, $this->dateEnd, $this->googleAdwordsBannerIds); //TODO сделать даты переменными
        $adwordsBannersInfo = $this->prepareGoogleAnalyticsReport( $adwordsBannersInfo );

        $this->saveAdsStatistic( array_merge($adwordsBannersInfo, $directBannerInfo) );
    }

    private function saveCallsStatistic( array $callsWithSessions ): void
    {
        $csv = new Csv( storage_path(sprintf('app/public/jobs/Dashboard/AdsProfitByCallsAndEmails/%s_calls.csv', $this->execution_id)) );
        $csv->openStream();

        $csv->insertRow([ 'источник', 'компания', '№ объявления', 'номер телефона', 'время начала звонка', 'заказы' ]);
        foreach ($callsWithSessions as $call){
            $ad_data = $call->integrated_campaign_data;

            $orderIds = '';
            foreach ($call->oneC_data as $oneC_data){
                $orderIds .= $oneC_data->order_id !== null ? $oneC_data->order_id."\n" : '';
            }

            $csv->insertRow([
                $ad_data->adv_system,
                $ad_data->ext_campaign_name,
                $ad_data->ext_campaign_id,
                $call->contact_phone_number,
                $call->start_time,
                $orderIds
            ]);
        }

        $csv->closeStream();
    }

    private function saveInvoicesStatistic( array $invoiceInfo ): void
    {
        $csv = new Csv( storage_path(sprintf('app/public/jobs/Dashboard/AdsProfitByCallsAndEmails/%s_invoices.csv', $this->execution_id)) );
        $csv->openStream();

        $csv->insertRow(['id заказа', 'дата создания заказа', 'сумма заказа', 'оплаченная сумма', 'оплачен ли', 'дата полной оплаты']);
        foreach ($invoiceInfo as $invoice) {
            $csv->insertRow([
                $invoice['order_id'],
                $invoice['generated'],
                $invoice['order_amount'],
                $invoice['paid_amount'],
                $invoice['is_paid'],
                $invoice['paid_date'],
            ]);
        }

        $csv->closeStream();
    }

    private function saveAdsStatistic( array $bannerInfo ): void
    {
        $fieldsMapping = [
            'adv_system' => [
                'google.adwords' => 'adv_system',
                'yandex.direct' => 'adv_system',
            ],
            'компания' => [
                'google.adwords' => 'ga:adwordsCampaignID',
                'yandex.direct' => 'CampaignId',
            ],
            '№ объявления' => [
                'google.adwords' => 'ga:adwordsCreativeID',
                'yandex.direct' => 'AdId',
            ],
            'показы' => [
                'google.adwords' => 'ga:impressions',
                'yandex.direct' => 'Impressions',
            ],
            'клики' => [
                'google.adwords' => 'ga:adClicks',
                'yandex.direct' => 'Clicks',
            ],
            'CTR' => [
                'google.adwords' => 'ga:CTR',
                'yandex.direct' => 'Ctr',
            ],
            'Затраты' => [
                'google.adwords' => 'ga:adCost',
                'yandex.direct' => 'Cost',
            ],
            'Дата' => [
                'google.adwords' => 'ga:date',
                'yandex.direct' => 'Date',
            ]
        ];

        $csv = new Csv( storage_path(sprintf('app/public/jobs/Dashboard/AdsProfitByCallsAndEmails/%s_ads.csv', $this->execution_id)) );
        $csv->openStream();

        // Заголовки файла
        $csv->insertRow(array_keys($fieldsMapping));

        foreach ($bannerInfo as $adInfo){

            $row = [];
            foreach ($fieldsMapping as $adPropertyNames) {

                $adPropertyName = $adPropertyNames[ $adInfo['adv_system'] ];
                $row[] = $adInfo[$adPropertyName];
            }
            $csv->insertRow($row);
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
    private function saveAdsIds(array $comagicCalls): void
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

    private function getInvoicesIds(array $comagicCalls): array
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

    private function prepareYandexDirectReport( array $directReport ): array
    {
        //trim headers
        array_shift($directReport);

        return array_map( function( $item ) {
            $item['adv_system'] = 'yandex.direct';
            return $item;
        }, $directReport);
    }

    private function prepareGoogleAnalyticsReport( array $analyticsReport ): array
    {
        return array_map( function( $item ) {
            $item['adv_system'] = 'google.adwords';
            return $item;
        }, $analyticsReport);
    }
}
