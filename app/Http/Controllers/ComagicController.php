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

    /**
     * @throws GuzzleException
     */
    public function handleCallsReport( FromToDateRequest $request ): View
    {
        $response = (new Comagic())->callInfo( $request->get('dateStart'), $request->get('dateEnd') );

        if ( isset( $response->error ) ) {

            return view('comagic/callsReportForm')
                ->withErrors( (new MessageBag())->add('custom_error', $response->error->message) );
        }

        return view('comagic/resultTable')
            ->with('data', $response->result->data)
            ->with('columns', ['visitor_id', 'contact_phone_number']);
    }
}
