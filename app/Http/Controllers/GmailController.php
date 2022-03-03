<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Contracts\View\View;
use App\Jobs\ParseManagersGmailsJob;

class GmailController extends Controller
{

    public function emailsEntriesForm(): View
    {
        return view('google/emailsEntriesForm');
    }

    public function emailsEntriesHandle( Request $request ): View
    {
        if ( !$request->get('emails') ) {
            return view('google/emailsEntriesForm')->withErrors( (new MessageBag())->add('error', 'Вставьте хотя бы один домен') );
        }

        $emailsArr = explode("\r\n", $request->get('emails'));

        ParseManagersGmailsJob::dispatchSync( $emailsArr );

        return view('google/emailsEntriesForm')->with('success', 'Задача поставлена в очередь на выполнение');
    }

}
