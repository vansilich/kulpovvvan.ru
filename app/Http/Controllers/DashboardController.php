<?php

namespace App\Http\Controllers;

use App\Handlers\MonthlyDashboard;
use Illuminate\Contracts\View\View;
use App\Http\Requests\FromToDateRequest;

class DashboardController extends Controller
{

    public function monthlyReportForm(): View
    {
        return view('dashboardReports/monthlySeoForm');
    }

    public function monthlyReportHandle(
        FromToDateRequest $request,
        MonthlyDashboard $monthlyDashboard
    )
    {

        $dateStart = $request->get('dateStart');
        $dateEnd = $request->get('dateEnd');

        $callsWithSessions = $monthlyDashboard->comagicCallsWithSessions($dateStart, $dateEnd);
        dd($callsWithSessions); //TODO удалить
    }
}
