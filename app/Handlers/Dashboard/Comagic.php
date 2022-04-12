<?php

namespace App\Handlers\Dashboard;

use App\Helpers\Api\Comagic as ComagicAPI;
use Carbon\Carbon;
use Exception;

class Comagic
{

    public function __construct(
        private ComagicAPI $comagic
    ){}

    /**
     * Fetch all calls where session info is set and
     *
     * @throws Exception
     */
    public function callsWithSessionAndAdInfo( string $dateStart, string $dateEnd ): array
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
        $userSessions = $this->filterPruneCallsWithoutAdInfo( $userSessionRequest->result->data );

        return $this->filterPruneCallsWithoutSessionInfo($allCallsByPeriod, $userSessions);
    }

    private function filterPruneCallsWithoutAdInfo( array $userSession ): array
    {
        $filteredSessions = [];
        foreach ($userSession as $value ){

            //If provided ad info save it
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

}
