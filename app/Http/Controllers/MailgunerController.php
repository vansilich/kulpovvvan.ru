<?php

namespace App\Http\Controllers;

use App\Helpers\Api\Mailganer;
use App\Models\NewEmailsManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class MailgunerController extends Controller
{

    public function unsubForm(): View
    {
        return view('mailganer/unsubForm');
    }

    public function handleUnsub( Request $request ): View
    {
        if ( !$request->has('emails') || $request->get('emails') == '' ) {
            return view('mailganer/unsubForm')->with('error', 'Введите хотябы один Email');
        }

        $emails_arr = explode( "\r\n", $request->get('emails'));

        $failed_emails = [];
        $mailganer = new Mailganer();

        foreach ($emails_arr as $email) {
            if ( !$mailganer->unsubscribe($email) ) {
                $failed_emails[] = $email;
            }
        }

        if ( !empty($failed_emails) ) {
            return view('mailganer/unsubForm')
                ->with( 'error', "Во время выполнения запроса c некоторыми имейлами произошла ошибка. Имейлы, которые не удалось отписать, оставлены input" )
                ->with( 'fails', implode("\r\n", $failed_emails) );
        }

        return view('mailganer/unsubForm')->with('success', 'все имейлы были отписаны');
    }

    public function ManagerEmailStatForm(): View
    {

        $table_headers = [];
        $managers = NewEmailsManager::select('manager')->distinct()->get()->toArray();

        foreach ($managers as $valueArr) {
            $table_headers[] = $valueArr['manager'];
        }
        $table_headers[] = 'date';

        $data = [];
        $NewEmailsManager_data = NewEmailsManager::all()->toArray();
        foreach ($NewEmailsManager_data as $value) {

            $date = $value['date'];
            $manager = $value['manager'];

            if ( isset($data[ $date ][ $manager ]) ) {
                $data[ $date ][ $manager ] += $value['count_new'];
            }
            else {
                $data[ $date ][ $manager ] = $value['count_new'];
            }
        }

        return view('mailganer/managerEmailStatTable')->with('data', $data)->with('headers', $table_headers);
    }

    public function logsByDate( Request $request )
    {
        $date = (new Carbon( $request->get('date') ))->format('Y.m.d');

        $path = storage_path() . "/app/public/manager_stats/$date/report.txt";

        return response()->download( $path );
    }
}
