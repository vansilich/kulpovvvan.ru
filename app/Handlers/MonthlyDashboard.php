<?php

namespace App\Handlers;

use App\Helpers\Api\Analytics;
use App\Helpers\Api\Comagic;
use App\Helpers\Api\YDirect;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\DB;

class MonthlyDashboard
{

    private array $adv_systems = [
        'yandex.direct',
        'google.adwords'
    ];

    private array $yandexDirectBannerIds;
    private array $googleAdwordsBannerIds;

    public function __construct(
        private Comagic $comagic
    ){}

    /**
     * Fetch calls and call`s session info from Comagic
     *
     * @throws Exception
     */
    public function comagicCallsWithSessions( string $dateStart, string $dateEnd ): array
    {
        $allCallsByPeriodFields = [ 'campaign_name', 'visitor_id', 'visitor_session_id', 'start_time', 'contact_phone_number'];
        $allCallsByPeriodRequest = $this->comagic->getReport('get.calls_report', $dateStart, $dateEnd, $allCallsByPeriodFields, [
            'field' => 'visitor_session_id',
            'operator' => '!=',
            'value' => null
        ]);
        if ( isset($allCallsByPeriodRequest->error) ) {
            throw new Exception($allCallsByPeriodRequest->error->message, $allCallsByPeriodRequest->error->code);
        }
        $allCallsByPeriod = $allCallsByPeriodRequest->result->data;

        $visitorsIds = [];
        foreach ( $allCallsByPeriod as $call ) {
            $visitorsIds[] = $call->visitor_id;
        }

        //trim hours, minutes and seconds;
        $dateStart = Carbon::parse( $dateEnd )->subDays(89)->toDateString();

        $sessionFields = [ 'id', 'date_time', 'visitor_id', 'integrated_campaign_data' ];
        $userSessionRequest = $this->comagic->getReport('get.visitor_sessions_report', $dateStart, $dateEnd, $sessionFields, [
            'field' => 'visitor_id',
            'operator' => 'in',
            'value' => $visitorsIds
        ]);
        if ( isset($userSessionRequest->error) ) {
            throw new Exception($userSessionRequest->error->message, $userSessionRequest->error->code);
        }
        $userSessions = $this->filterPruneSessionsWithoutAdInfo( $userSessionRequest->result->data );

        return $this->filterPruneCallsWithoutSessionInfo($allCallsByPeriod, $userSessions);
    }

    private function filterPruneSessionsWithoutAdInfo( array $userSession ): array
    {
        $filteredSessions = [];
        foreach ($userSession as $value ){

            //If not provided ad info, then don`t save it
            if ($value->integrated_campaign_data->current_banner_id !== null) {
                $filteredSessions[ $value->id ] = $value;
            }
        }

        return $filteredSessions;
    }

    private function filterPruneCallsWithoutSessionInfo( array $calls, array $callSessions ): array
    {
        $callWithAdInfo = [];

        foreach ($calls as $call) {

            if ( isset($callSessions[ $call->visitor_session_id ]) ){
                $call->integrated_campaign_data = $callSessions[ $call->visitor_session_id ]->integrated_campaign_data;
                $callWithAdInfo[] = $call;
            }
        }

        return $callWithAdInfo;
    }

    /**
     * Fetching all call data by period from 1C upload table 'PhoneOrder'
     *
     * @throws Exception
     */
    public function getOneCPhoneOrders( string $startDate, string $endDate ): array
    {
        $phoneOrders = DB::connection('gb_testfl')
            ->select('SELECT * FROM PhoneOrder where call_date >= ? AND call_date <= ?', [$startDate, $endDate]);

        $phoneOrdersWithPhoneKeys = [];
        foreach ($phoneOrders as $phoneOrder){

            $phoneOrder->phone = '7'.$phoneOrder->phone;
            $phoneOrdersWithPhoneKeys[$phoneOrder->phone][] = $phoneOrder;
        }

        return $phoneOrdersWithPhoneKeys;
    }

    /**
     * Assign 1C record of call to Comagic objects
     */
    public function compareOneCAndComagicCalls( array $comagicCalls, array $oneCCalls ): array
    {

        foreach ($comagicCalls as $comagicCall){

            if ( isset($oneCCalls[ $comagicCall->contact_phone_number ]) ) {
                $comagicCall->oneCData = $oneCCalls[ $comagicCall->contact_phone_number ];
            }
        }

        //Delete calls without 1C data
        foreach ($comagicCalls as $key => $comagicCall) {
            if (!isset($comagicCall->oneCData)){
                unset( $comagicCalls[$key] );
            }
        }

        return $comagicCalls;
    }

    /**
     * @throws GuzzleException
     */
    public function getDirectBannersInfo(string $dateStart, string $dateEnd, array $comagicCalls ): array
    {
        $bannerIds = [];
        foreach ($comagicCalls as $call) {
            if ($call->integrated_campaign_data->adv_system === 'yandex.direct'){
                $bannerIds[] = $call->integrated_campaign_data->current_banner_id;
            }
        }
        return (new YDirect())->adsReport($dateStart, $dateEnd, $bannerIds);
    }

    public function getAdwordsBannersInfo(string $dateStart, string $dateEnd, array $comagicCalls)
    {
        $bannerIds = [];
        foreach ($comagicCalls as $call) {
            if ($call->integrated_campaign_data->adv_system === 'google.adwords'){
                $bannerIds[] = $call->integrated_campaign_data->current_banner_id;
            }
        }

        $analyticsAPI = new Analytics();
        $analyticsAPI->getReport([
            'metrics' => [],
            'dimensions' => [],
            'metricFilterClauses' => [
                [
                    'metricName' => '',
                    'operator' => '',
                    'comparisonValue' => ''
                ]
            ]
        ], $dateStart, $dateEnd);
    }

}
