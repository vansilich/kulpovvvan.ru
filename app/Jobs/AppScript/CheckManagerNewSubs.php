<?php

namespace App\Jobs\AppScript;

use App\Helpers\Api\Mailganer;
use App\Models\Manager;
use App\Models\NewEmailsManager;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CheckManagerNewSubs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $emails;
    private string $manager;

    public function __construct( array $data )
    {
        $this->emails = $data['data'];
        $this->manager = $data['file_path'];
    }

    /**
     * @throws GuzzleException
     */
    public function handle(Mailganer $mailganer): void
    {
        //лог исходных данных запроса
        $input_logger = Log::build(['driver' => 'single', 'path' => storage_path('logs/jobs/CheckManagerNewSubs/input.log')]);
        //лог хода выполнения
        $debug_logger = Log::build(['driver' => 'single', 'path' => storage_path('logs/jobs/CheckManagerNewSubs/debug.log')]);

        $input_logger->debug( serialize([
            'emails' => $this->emails,
            'manager' => $this->manager
            ])
        );

        $total_not_founded = 0;
        $today = (new Carbon())->format('Y.m.d');

        $managers = Manager::select('nickname', 'mailganer_list_id')
            ->whereNotNull('mailganer_list_id')
            ->get()
            ->toArray();
        $MainListId = Manager::select('mailganer_list_id')
            ->where('nickname', '=', $this->manager)
            ->first()
            ->mailganer_list_id;

        Storage::append("/public/manager_stats/$today/report.txt","\n$this->manager - $today\n");

        foreach ($this->emails as $email) {

            $email = mb_strtolower($email, 'UTF-8');
            $is_found = false;

            if ( !$email || !is_valid_email($email) ) {
                continue;
            }

            foreach ($managers as $value) {

                $res = $mailganer->subscriberInfo( [ 'email' => $email, 'source' => $value['mailganer_list_id'] ] );
                if ($res->count != 0) {
                    $debug_logger->debug("$email менеджера $this->manager уже в списке менеджера " . $value['nickname']);

                    $is_found = true;
                    break;
                }
            }

            // Если имейл не найден в списках (новый подписчик)
            if ( !$is_found ) {

                $mailganer->subscribeToList($email, $MainListId);
                $total_not_founded++;
                Storage::append("/public/manager_stats/$today/report.txt", $email);
            }
        }

        NewEmailsManager::create([
            'manager' => $this->manager,
            'count_new' => $total_not_founded,
            'date' => $today,
        ]);
    }
}
