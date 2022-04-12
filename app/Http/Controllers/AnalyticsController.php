<?php

namespace App\Http\Controllers;

use App\Helpers\Api\Analytics;
use App\Helpers\Csv;
use Carbon\Carbon;
use Google\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AnalyticsController extends Controller
{

    public function index()
    {
        return view('google/analyticsReport')->with('errors');
    }

    /**
     * @throws Exception
     */
    public function handle( Request $request ): BinaryFileResponse
    {
        $analyticsReporting = new Analytics;

        $headers = ['AdId', 'campaign', 'AdGroupName', 'AdGroupId', 'Date', 'Conversions_11', 'CostPerConversion_11', 'Conversions_8', 'CostPerConversion_8', 'Ctr', 'Clicks', 'Impressions'];

        //идентификатор цели 8 - id 89864822, идентификатор цели 11 - id 89814803
        $analyticsRequest = [
            'metrics' => ['ga:goal11ConversionRate', 'ga:adCost/ga:goal11Completions', 'ga:goal8ConversionRate', 'ga:adCost/ga:goal8Completions', 'ga:CTR', 'ga:adClicks', 'ga:impressions'],
            'dimensions' => ['ga:adwordsCreativeID', 'ga:campaign', 'ga:adGroup', 'ga:adwordsAdGroupID', 'ga:date'],
        ];

        $file_name = Carbon::now()->timestamp.'.csv';
        $csv = new Csv( storage_path("app/public/$file_name") );
        $csv->openStream();

        $csv->insertRow($headers);

        $response = $analyticsReporting->customReport( $analyticsRequest, $request->get('dateStart'), $request->get('dateEnd') );

        $rows = $response[0]->getData()->getRows();
        for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
            $row = $rows[$rowIndex];

            $dimensions = $row->getDimensions();
            $metrics = $row->getMetrics()[0]->values;
            $data = array_merge($dimensions, $metrics);

            if ($data[0] != '(not set)') {
                $data[4] = Carbon::createFromFormat('Ymd', $data[4])->format('Y.m.d');

                $csv->insertRow( $data );
            }
        }


        $csv->closeStream();
        $csv->deleteAfterResp();

        return response()->download( $csv->filePath );
    }

}
