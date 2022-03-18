<?php

namespace App\Jobs\Gmail;

use App\Helpers\Api\Gmail;
use App\Helpers\Csv;
use App\Helpers\Email;
use App\Helpers\Phone;
use App\Models\Manager;
use Carbon\Carbon;
use Exception;
use Generator;
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
    public function handle(Gmail $gmail)
    {
        $this->getParsedData();

        if ( empty($this->triggers) ) {
            $this->setupTriggers();
        }

        $this->gmail_api = $gmail;
        $this->gmail_api->setClient( $this->managerAlias );
        $this->gmail_api->setupService();

        $name = sprintf("%s.csv", $this->managerAlias);
        $csv = new Csv( storage_path("app/public/jobs/Gmail/PhonesByTriggers/$name") );
        $file_exists = file_exists( $csv->filePath );

        $csv->openStream('a');

        //setup headers in file
        if ( !$file_exists ) {
            $csv->insertRow(['trigger', 'email', 'from', 'to', 'emails', 'phones']);
        }

        $this->storeParsedData( $csv );

        $csv->closeStream();

        //clear cache
        Storage::disk('local')->delete("public/jobs/Gmail/PhonesByTriggers/cache/$this->managerAlias");
    }

    /**
     * @throws Exception
     */
    private function storeParsedData(Csv $csv): void
    {
        //iterate and save api data
        foreach ( $this->messagesListIterator() as $parsed_data){

            if ( empty($parsed_data) ) {
                continue;
            }

            foreach ($parsed_data as $trigger => $data) {
                foreach ($data as $email => $values) {
                    foreach ($values as $value) {

                        $csv->insertRow([
                            $trigger,
                            $email,
                            $value['from'],
                            $value['to'],
                            $value['emails'] ? implode("\n", $value['emails']) : null,
                            $value['phones'] ? implode("\n", $value['phones']) : null,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Iterate gmails, cache state after every parsed chunk
     *
     * @throws Exception
     */
    private function messagesListIterator(): Generator
    {

        foreach ($this->triggers as $key => $trigger) {

            $parsed_data = [];

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
                    $emails = Email::searchByRegexp( $text );

                    $parsed_data[ $trigger ][ $email ][] = [
                        'from' => $from,
                        'to' => $to,
                        'emails' => $emails[0] ? array_unique($emails[0]) : null,
                        'phones' => $phones[0] ? array_unique($phones[0]) : null
                    ];
                }

                $nextPageToken = $messagesList->getNextPageToken();
                $this->params['pageToken'] = $nextPageToken;

            } while ( $nextPageToken );

            unset( $this->triggers[$key], $this->params );

            $this->cacheState();
            yield $parsed_data;
        }
    }

    /**
     * Serialize parsed data
     */
    private function cacheState(): void
    {
        file_put_contents( $this->cacheFile, serialize([
            'triggers' => $this->triggers,
            'pageToken' => $this->params['pageToken'] ?? null,
        ]));
    }

    /**
     * Get previous parsed data of this job
     */
    private function getParsedData(): void
    {

        if ( file_exists( $this->cacheFile ) ) {

            $file_data = unserialize( file_get_contents( $this->cacheFile ));
            if ( is_array($file_data) ) {
                $this->triggers = $file_data['triggers'];
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
