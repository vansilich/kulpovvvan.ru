<?php

namespace App\Http\Controllers;

use App\Helpers\Api\Comagic;
use Illuminate\Contracts\View\View;
use App\Http\Requests\FromToDateRequest;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\MessageBag;

class ComagicController extends Controller
{

    public function callsReportForm(): View
    {
        return view('comagic/callsReportForm');
    }

    public function handleCallsReport( FromToDateRequest $request ): View
    {
        $fields = [
            'visitor_id',
            'contact_phone_number',
        ];
        $response = (new Comagic())->getReport( 'get.calls_report', $request->get('dateStart'), $request->get('dateEnd'), $fields );

        if ( isset( $response->error ) ) {

            return view('comagic/callsReportForm')
                ->withErrors( (new MessageBag())->add('custom_error', $response->error->message) );
        }

        return view('comagic/resultTable')
            ->with('data', $response->result->data)
            ->with('columns', $fields);
    }
}
