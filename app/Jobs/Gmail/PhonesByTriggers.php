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

class PhonesByTriggers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Gmail $gmail_api;

    private string $cacheFile;
    private array $parsed_data = [];
    private array $triggers = [];
    private string $managerMail;

    public function __construct(
        private string $managerAlias,
    ){
        $this->managerMail = Manager::where('nickname', $this->managerAlias)->first()->toArray()['mail'];
        $this->cacheFile = storage_path("app/public/parse-phone-by-triggers/" . $this->managerAlias);
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
//        $triggers_count = count($this->triggers);

        foreach ($this->triggers as $key => $trigger) {

            //Searching by trigger in subject
            $params = [ 'q' => "subject:$trigger" ];
            unset( $this->triggers[ $key ] );

            //TODO удалить
//            dump( "Тригер $trigger; Осталось тригеров: " . $triggers_count - $key - 1);

            do {
                $messagesList = $this->gmail_api->queryMessages($params);

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

                $this->cacheState();

                $nextPageToken = $messagesList->getNextPageToken();
                $params = [ 'pageToken' => $nextPageToken ];

            } while ( $nextPageToken );

        }

        //конец парсинга
        $name = sprintf("%s_%s.csv", $this->managerAlias, Carbon::now()->timestamp);
        $csv = new Csv( storage_path("app/public/$name") );
        $csv->openStream();

        foreach ($this->parsed_data as $managerAlias => $triggers) {
            foreach ( $triggers as $trigger => $phones ) {
                foreach ($phones as $phone) {
                    $csv->insertRow([$managerAlias, $trigger, $phone]);
                }
            }

        }

        $csv->closeStream();
    }

    /**
     * serialize parsed data
     */
    private function cacheState(): void
    {
        file_put_contents( $this->cacheFile, serialize([
            'triggers' => $this->triggers,
            'parsed_data' => $this->parsed_data
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
