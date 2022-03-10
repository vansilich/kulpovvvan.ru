<?php

namespace App\Http\Controllers;

use App\Helpers\Api\Comagic;
use App\Helpers\Api\Roistat;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use App\Http\Requests\FromToDateRequest;
use Illuminate\Support\MessageBag;

class DashboardController extends Controller
{


    public function monthlyReportForm(): View
    {
        return view('dashboardReports/monthlySeoForm');
    }

    public function monthlyReportHandle( FromToDateRequest $request )
    {

        $dateStart = $request->get('dateStart');
        $dateEnd = $request->get('dateEnd');

        $Comagic = new Comagic();

        $allCallsByPeriodFields = [ 'campaign_name', 'visitor_id', 'visitor_session_id', 'start_time', 'sale_date', 'sale_cost' ];
        $allCallsByPeriodRequest = $Comagic->getReport('get.calls_report', $dateStart, $dateEnd, $allCallsByPeriodFields, [
            'field' => 'visitor_session_id',
            'operator' => '!=',
            'value' => null
        ]);
        $allCallsByPeriod = $allCallsByPeriodRequest->result->data;

        $visitorsIds = [];
        foreach ( $allCallsByPeriod as $call ) {
            $visitorsIds[] = $call->visitor_id;
        }

        //trim hours, minutes and seconds;
        $dateStart = Carbon::parse( $dateEnd )->subDays(89)->toDateString();

        $sessionFields = [ 'id', 'date_time', 'visitor_id', 'integrated_campaign_data' ];
        $userSessionRequest = $Comagic->getReport('get.visitor_sessions_report', $dateStart, $dateEnd, $sessionFields, [
            'field' => 'visitor_id',
            'operator' => 'in',
            'value' => $visitorsIds
        ]);
        $userSession = $userSessionRequest->result->data;

        dd($userSession);
        foreach ($userSession as $session) {
            if ($session->id === $call->visitor_session_id) {
                $call->banner_name = $session->integrated_campaign_data->banner_name;
                break;
            }
        }

    }
}
