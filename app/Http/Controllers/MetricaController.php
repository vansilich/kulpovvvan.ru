<?php

namespace App\Http\Controllers;

use App\Helpers\Api\YMetrica;
use App\Helpers\Csv;
use App\Models\ObservableUrl;
use App\Models\UrlViewsReport;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\FromToDateRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MetricaController extends Controller
{

    public function pagesReportForm(): View
    {
        return view('metrika.pagesReport');
    }

    public function printPagesReportForm(): View
    {
        $to = UrlViewsReport::select('day')->distinct()->orderBy('day', 'asc')->get()->last();
        $from = null;

        if ($to) {
            $to = $to->day;
            $from = Cache::get('FNUV_lastFetchedDate');
        }

        return view('metrika.printPagesReport')->with('from', $from)->with('to', $to);
    }

    /**
     * @throws GuzzleException
     */
    public function pagesReportFormHandle(FromToDateRequest $request ): void
    {
        YMetrica::pageReport( $request->get('dateStart'), $request->get('dateEnd') );
    }

    public static function printPagesReportHandle( FromToDateRequest $request ): BinaryFileResponse
    {
        $dateFrom = $request->get('dateStart');
        $dateTo = $request->get('dateEnd');

        $file_name = Carbon::now()->timestamp.'.csv';
        $csv = new Csv( storage_path('app/public').'/'.$file_name );

        $csv->openStream();

        $headers = [ 'url' ];
        $is_headers_set = false;

        $urls = ObservableUrl::all();

        foreach ($urls as $url) {

            /**
             * We search days that`s already exists and are in the range of input dates.
             * The intervals come in 'ASC' order (it`s important for filtering)
             **/
            $savedDays = $url->viewsReports()
                ->where('day', '>=', $dateFrom )
                ->where('day', '<=', $dateTo )
                ->orderBy('day')
                ->get()
                ->toArray();

            $reportsByWeeks = splitByWeeks( $savedDays );

            $row = [];
            $row[] = $url->url;
            foreach ($reportsByWeeks as $week) {

                $views = 0;
                foreach ($week as $day) {
                    $views += $day['views'];
                }
                $row[] = $views;
            }

            if ( !$is_headers_set ) {
                $is_headers_set = true;

                foreach ($reportsByWeeks as $week) {
                    $start_date = $week[0]['day'];
                    $end_date = $week[ array_key_last($week) ]['day'];

                    $headers[] = $start_date . '---' . $end_date;
                }

                $csv->insertRow( $headers );
            }

            $csv->insertRow( $row );
        }

        $csv->closeStream();
        $csv->deleteAfterResp();

        return response()->download( $csv->filePath );
    }

}
