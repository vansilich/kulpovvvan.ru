<?php

namespace App\Http\Controllers;

use App\Helpers\Api\BigQuery;
use App\Helpers\Csv;
use App\Helpers\TxtHandler;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BigQueryController extends Controller
{

    public function findYM_UIDForm(): View
    {
        return view('google/findYM_UID');
    }

    public function findYM_UIDHandle( Request $request ): View|BinaryFileResponse
    {

        if ( !$request->hasFile('txtFile') ) {
            return view('google/findYM_UID')->withErrors( (new MessageBag())->add('error', 'Файл не был загружен') );
        }

        $api = new BigQuery();
        $file = $request->file('txtFile');

        $CLIENT_NAMES = [];
        foreach (TxtHandler::fileRows($file) as $row) {

            $splitted_row = explode("\t", $row);

            if ( isset($splitted_row[1])) {
                $CLIENT_NAMES[] = "'".trim( explode("\t", $row)[1] )."'";
            }

        }
        $results = $api->getYM_UidByRoistat_Id( implode(", ", array_unique($CLIENT_NAMES)) );

        $YM_UIDS = [];
        foreach ($results as $value) {

            if ( $value['YM_UID'] == '' || $value['YM_UID'] == 'null') continue;

            $YM_UIDS[] = $value['YM_UID'];
        }

        $name = Carbon::now()->timestamp . '.csv';
        $csv = new Csv(storage_path('app/public').'/'.$name);
        $csv->openStream();

        //headers
        $csv->insertRow( ['YM_UIDS'] );

        //data
        foreach ($YM_UIDS as $client_name => $value) {
            $csv->insertRow( [$value] );
        }

        $csv->closeStream();
        $csv->deleteAfterResp();

        return response()->download( $csv->filePath );
    }
}
