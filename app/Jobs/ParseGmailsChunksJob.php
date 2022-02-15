<?php

namespace App\Jobs;

use App\Helpers\CsvHandler;
use Carbon\Carbon;
use Exception;
use App\Helpers\Api\Gmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;

class ParseGmailsChunksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Gmail $gmail_api;
    private array $parsed_data = [];
    private LoggerInterface $logger;
    private string $manager = 'kir';

    public string $phoneRegexp = '#(?:\+?(8|7)[\- ]?)(\(?\d{3}\)?[\- ]?)[\d\- ]{7,10}#um';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private array $email_regexps,
        private array $params = [],
    ){}

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        $this->getParsedData();

        $this->gmail_api = new Gmail();
        $this->gmail_api->setClient( $this->manager );
        $this->gmail_api->setupService();

        $this->messagesListIterator( $this->params );
    }

    /**
     * @throws Exception
     */
    private function messagesListIterator( array $params = [] )
    {
        $messagesList = $this->gmail_api->queryMessages( $params );

        foreach ( $this->gmail_api->messagesTextIterator( $messagesList ) as $email_data) {
            list('from' => $from, 'to' => $to, 'text' => $text) = $email_data;

            if ( !$text ) continue;
            foreach ($this->email_regexps as $regexp) {

                preg_match_all($regexp, $from.$to.$text, $emails);

                if ( empty($emails[0]) ) continue;
                //цикл нужен для того, чтобы если в одном письме несколько нужных email адресов, сохранить номер телефона для каждого
                foreach ($emails[0] as $email) {

                    $email = mb_strtolower($email, 'UTF-8');
                    preg_match_all($this->phoneRegexp, $text, $phones);

                    if ( empty($phones[0]) ) continue;
                    foreach ($phones[0] as $phone) {

                        if ( !in_array($phone, $this->parsed_data[ $email ]) ){
                            $this->parsed_data[ $email ][] = $phone;
                        }
                    }
                }

            }
        }

        $this->cacheState();

        if ( $messagesList->getNextPageToken() ) {

            static::dispatch(
                $this->email_regexps,
                [ 'pageToken' => $messagesList->getNextPageToken() ],
            )->delay( now()->addSecond() );
            return;
        }

        //конец парсинга
        $name = $this->manager . '_' . Carbon::now()->timestamp . '.csv';
        $csv = new CsvHandler(storage_path('app/public').'/'.$name );
        $csv->openStream();

        foreach ($this->parsed_data as $manager => $phones) {
            foreach ($phones as $phone) {
                $csv->insertRow([$manager, $phone]);
            }
        }

        $csv->closeStream();

        //Удаляем кеш файл
        Storage::disk('local')->delete('public/parse-gmail/' . $this->manager);
    }

    /**
     * Сериализует и сохраняет спарсенные данные
     */
    private function cacheState(): void
    {
        $path = storage_path('app/public') . '/parse-gmail/' . $this->manager;
        file_put_contents( $path, serialize($this->parsed_data) );
    }

    /**
     * Получает спарсенные данные из файла
     */
    private function getParsedData(): void
    {
        $path = storage_path('app/public') . '/parse-gmail/' . $this->manager;

        if ( file_exists( $path ) ) {

            try {

                $file_data = unserialize( file_get_contents( $path ));
                if ( is_array($file_data) ) {
                    $this->parsed_data = $file_data;
                }
            } catch ( Exception $exception ) {
                //$this->parsed_data по умолчанию равен [], поэтому в случае ошибки он останется таким же
            }

        }
    }

}
