<?php

namespace App\Http\Controllers;

use App\Jobs\ParseManagersGmailsJob;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\MessageBag;

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
