<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use App\Http\Requests\FromToDateRequest;
use App\Jobs\Dashboard\AdsProfitByCallsAndEmails;

class DashboardController extends Controller
{

    public function monthlyReportForm(): View
    {
        return view('dashboardReports/monthlySeoForm');
    }

    public function monthlyReportHandle(FromToDateRequest $request)
    {
        $dateStart = $request->get('dateStart');
        $dateEnd = $request->get('dateEnd');

        AdsProfitByCallsAndEmails::dispatch( $dateStart, $dateEnd )->delay( now()->addSecond() )->onQueue('dashboard');
    }
}
