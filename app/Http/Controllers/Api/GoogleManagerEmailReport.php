<?php

namespace App\Http\Controllers\Api;

use App\Jobs\AppScript\CheckManagerNewSubs;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GoogleManagerEmailReport extends Controller
{

    /**
     * Handle the incoming request.
     *
     * @param Request $request
     */
    public function __invoke(Request $request)
    {
        $data = $request->json()->all();

        CheckManagerNewSubs::dispatch( $data )->onQueue('mailganer')->delay( now()->addSecond() );
    }
}
