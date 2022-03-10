<?php

namespace App\Http\Controllers\Api;

use App\Models\Manager;
use App\Models\NewEmailsManager;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use App\Helpers\Api\Mailganer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Throwable;

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

        //лог исходных данных запроса
        $input_logger = Log::build(['driver' => 'single', 'path' => storage_path('logs/api/mailganer/GoogleManagerEmailReport/input.log') ]);
        //лог хода выполнения
        $debug_logger = Log::build(['driver' => 'single', 'path' => storage_path('logs/api/mailganer/GoogleManagerEmailReport/debug.log') ]);

        $input_logger->debug( serialize($data) );

        $data = !empty($data) ? $data : die();

        $total_not_founded = 0;
        $emails = $data['data'];
        $manager = explode('_', $data['file_path'])[0] ?? die();
        $today = (new Carbon())->format('Y.m.d');

        $managers = Manager::select('nickname', 'mailganer_list_id')->get()->toArray();
        $MainListId = Manager::select('mailganer_list_id')->where('nickname', '=', $manager)->first()->mailganer_list_id;

        Storage::append("/public/manager_stats/$today/report.txt","\n$manager - $today\n");

        foreach ($emails as $email) {

            $email = mb_strtolower($email, 'UTF-8');
            if ( !$email || !is_valid_email($email) ) continue;

            $is_found = false;
            foreach ($managers as $value) {

                try {

                    //обработка throttling ошибок функцией limitedFuncRetry()
                    $res = limitedFuncRetry(5, 2,
                        fn() => Mailganer::subscriberInfo( [ 'email' => $email, 'source' => $value['mailganer_list_id'] ] )
                    );
                } catch ( Throwable $exception ) {
                    $debug_logger->error( $exception->getMessage() );
                    continue;
                }

                if ($res->count != 0) {
                    $is_found = true;
                    break;
                }
            }

            // Если имейл не найден в списках (новый подписчик)
            if ( !$is_found ) {
                try {
                    Mailganer::subscribeToList($email, $MainListId);
                    $total_not_founded++;
                    Storage::append("/public/manager_stats/$today/report.txt", $email);
                } catch ( GuzzleException $exception ) {
                    $debug_logger->error( $exception->getMessage() );
                }
            }
        }

        NewEmailsManager::create([
            'manager' => $manager,
            'count_new' => $total_not_founded,
            'date' => $today,
        ]);
    }
}
