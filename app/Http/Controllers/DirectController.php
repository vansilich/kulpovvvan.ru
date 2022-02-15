<?php

namespace App\Http\Controllers;

use App\Http\Requests\FromToDateRequest;
use Exception;
use App\Helpers\Api\YDirect;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class DirectController extends Controller
{


    public function index()
    {
        return view('directReport');
    }

    /**
     * @throws Exception
     */
    public function handle( FromToDateRequest $request )
    {

        $csv_name = YDirect::getReport( $request->get('dateStart'), $request->get('dateEnd') );
        $csv_path = storage_path('app/public')."/$csv_name";

        App::terminating( function () use ($csv_name) {
            Storage::disk('local')->delete('public/'.$csv_name);
        });

        return response()->download($csv_path);
    }
}
