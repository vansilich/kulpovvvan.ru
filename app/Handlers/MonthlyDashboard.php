<?php

namespace App\Handlers;

use App\Helpers\Api\Comagic;
use Carbon\Carbon;
use Exception;

class MonthlyDashboard
{

    public function __construct(
        private Comagic $comagic
    ){}

    /**
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
        $userSessions = $this->replaceKeysOnSessionId( $userSessionRequest->result->data );

        foreach ($allCallsByPeriod as $call) {
            if ( isset($userSessions[ $call->visitor_session_id ]) ){
                $call->integrated_campaign_data = $userSessions[ $call->visitor_session_id ]->integrated_campaign_data;
            }
        }
        return $allCallsByPeriod;
    }

    public function replaceKeysOnSessionId( array $allCallsByPeriod ): array
    {
        foreach ($allCallsByPeriod as $key => $value){
            $allCallsByPeriod[ $value->id ] = $value;
            unset( $allCallsByPeriod[$key] );
        }
        return $allCallsByPeriod;
    }

}
