<?php

namespace App\Http\Controllers;

use App\Jobs\Gmail\PhonesByTopDomains;
use App\Jobs\Gmail\PhonesByTriggers;
use App\Models\Manager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;

class GmailController extends Controller
{

    public function emailsEntriesForm(): View
    {
        return view('google/emailsEntriesForm');
    }

    public function triggersEntriesForm(): View
    {
        return view('google/dataByTriggers');
    }

    public function emailsEntriesHandle( Request $request ): View
    {
        if ( !$request->get('emails') ) {
            return view('google/emailsEntriesForm')->withErrors( (new MessageBag())->add('error', 'Вставьте хотя бы один домен') );
        }

        $emailsArr = explode("\r\n", $request->get('emails'));

        PhonesByTopDomains::dispatch( 'kir', $emailsArr )->delay( now()->addSecond() );

        return view('google/emailsEntriesForm')->with('success', 'Задача поставлена в очередь на выполнение');
    }

    public function triggersEntriesHandle( Request $request ): View
    {
//        if ( !$request->get('triggers') ) {
//            return view('google/dataByTriggers')->withErrors( (new MessageBag())->add('error', 'Вставьте хотя бы один триггер') );
//        }
//
//        $triggersArr = explode("\r\n", $request->get('triggers'));

        $managers = Manager::whereNotNull('mail')->get()->toArray();

        foreach ($managers as $manager) {

            PhonesByTriggers::dispatch( $manager['nickname'] )
                ->delay( now()->addSecond() );
        }

        return view('google/dataByTriggers')->with('success', 'Задача поставлена в очередь на выполнение');
    }

}
