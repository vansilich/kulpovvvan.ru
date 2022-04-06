<?php

namespace App\Http\Controllers;

use App\Handlers\MonthlyDashboard;
use App\Helpers\Api\YDirect;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\View\View;
use App\Http\Requests\FromToDateRequest;

class DashboardController extends Controller
{

    public function monthlyReportForm(): View
    {
        return view('dashboardReports/monthlySeoForm');
    }

    /**
     * @throws Exception|GuzzleException
     */
    public function monthlyReportHandle(FromToDateRequest $request, MonthlyDashboard $monthlyDashboard)
    {
        $dateStart = $request->get('dateStart');
        $dateEnd = $request->get('dateEnd');

        $callsWithSessions = $monthlyDashboard->comagicCallsWithSessions($dateStart, $dateEnd);
        $phoneOrders = $monthlyDashboard->getOneCPhoneOrders($dateStart, $dateEnd);
        $callsWithSessions = $monthlyDashboard->compareOneCAndComagicCalls( $callsWithSessions, $phoneOrders );

        $directBannerInfo = $monthlyDashboard->getDirectBannersInfo($dateStart, $dateEnd, $callsWithSessions);
//        $adwordsBannersInfo = $monthlyDashboard->getAdwordsBannersInfo($dateStart, $dateEnd, $callsWithSessions);
        dd($directBannerInfo); //TODO удалить
    }
}
