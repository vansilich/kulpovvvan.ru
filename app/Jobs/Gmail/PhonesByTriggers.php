<?php

namespace App\Jobs\Gmail;

use App\Helpers\Api\Gmail;
use App\Helpers\Csv;
use App\Helpers\Email;
use App\Helpers\Phone;
use App\Models\Manager;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class PhonesByTriggers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Gmail $gmail_api;

    private string $cacheFile;
    private string $managerMail;

    private array $params = [];
    private array $parsed_data = [];
    private array $triggers = [];

    public function __construct(
        private string $managerAlias,
    ){
        $this->managerMail = Manager::where('nickname', $this->managerAlias)->first()->toArray()['mail'];
        $this->cacheFile = storage_path("app/public/jobs/Gmail/PhonesByTriggers/cache/" . $this->managerAlias);
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        $this->getParsedData();

        if ( empty($this->triggers) ) {
            $this->setupTriggers();
        }

        $this->gmail_api = new Gmail();
        $this->gmail_api->setClient( $this->managerAlias );
        $this->gmail_api->setupService();

        $this->messagesListIterator();
    }

    /**
     * @throws Exception
     */
    private function messagesListIterator(): void
    {
        foreach ($this->triggers as $key => $trigger) {

            //Searching by trigger in subject
            $this->params['q'] = "subject:$trigger";

            do {
                $messagesList = $this->gmail_api->queryMessages($this->params);

                foreach ( $this->gmail_api->messagesTextIterator( $messagesList ) as $email_data) {
                    list('from' => $from, 'to' => $to, 'text' => $text) = $email_data;

                    $externalEmail = preg_match( '#'.preg_quote($this->managerMail).'#u', $from) ? $to : $from;
                    $email = Email::searchByRegexp( $externalEmail );

                    if ( empty($email[0]) ) continue;

                    $email = mb_strtolower($email[0][0], 'UTF-8');
                    $phones = Phone::searchByRegexp( $text );

                    if ( empty($phones) ) {
                        $this->parsed_data[ $email ][ $trigger ][] = null;
                        continue;
                    }

                    foreach ($phones[0] as $phone) {

                        $this->parsed_data[ $email ][ $trigger ][] = $phone;
                    }
                }

                $nextPageToken = $messagesList->getNextPageToken();
                $this->params['pageToken'] = $nextPageToken;

                $this->cacheState();
            } while ( $nextPageToken );

            unset( $this->triggers[$key] );
            $this->params = [];
        }

        //конец парсинга
        $name = sprintf("%s_%s.csv", $this->managerAlias, Carbon::now()->timestamp);
        $csv = new Csv( storage_path("app/public/jobs/Gmail/PhonesByTriggers/$name") );
        $csv->openStream();

        foreach ($this->parsed_data as $managerAlias => $triggers) {
            foreach ( $triggers as $trigger => $phones ) {
                foreach ($phones as $phone) {
                    $csv->insertRow([$managerAlias, $trigger, $phone]);
                }
            }
        }

        $csv->closeStream();

        Storage::disk('local')->delete('public/jobs/Gmail/PhonesByTriggers/cache/' . $this->managerAlias);
    }

    /**
     * serialize parsed data
     */
    private function cacheState(): void
    {
        file_put_contents( $this->cacheFile, serialize([
            'triggers' => $this->triggers,
            'parsed_data' => $this->parsed_data,
            'pageToken' => $this->params['pageToken'] ?? null,
        ]));
    }

    /**
     * get previous parsed data of this job
     */
    private function getParsedData(): void
    {

        if ( file_exists( $this->cacheFile ) ) {

            $file_data = unserialize( file_get_contents( $this->cacheFile ));
            if ( is_array($file_data) ) {
                $this->triggers = $file_data['triggers'];
                $this->parsed_data = $file_data['parsed_data'];
                $this->params['pageToken'] = $file_data['pageToken'];
            }
        }
    }

    private function setupTriggers()
    {
        $triggers = new Csv( base_path('data/output.csv') );
        $triggers->openStream('r');

        while ( ($trigger = fgetcsv($triggers->stream)) !== false ) {

            $this->triggers[] = $trigger[0];
        }

        $triggers->closeStream();
    }

}
