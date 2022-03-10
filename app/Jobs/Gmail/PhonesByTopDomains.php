<?php

namespace App\Jobs\Gmail;

use App\Helpers\Csv;
use App\Helpers\Phone;
use Carbon\Carbon;
use Exception;
use App\Helpers\Api\Gmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;

class PhonesByTopDomains implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Gmail $gmail_api;
    private LoggerInterface $logger;

    private array $parsed_data = [];
    private array $email_regexps = [];

    public function __construct(
        private string $managerAlias,
        private array $domains
    ){}

    /**
     * @throws Exception
     */
    public function handle()
    {
        $this->setRegexps();
        $this->getParsedData();

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

        foreach ($this->email_regexps as $domain => $regexp) {

            //Gmail regexp for searching
            $params = [ 'q' => "from:(*@*$domain) OR to:(*@*$domain)" ];

            do {
                $messagesList = $this->gmail_api->queryMessages( $params );

//                dump($params);

                foreach ( $this->gmail_api->messagesTextIterator( $messagesList ) as $email_data) {
                    list('from' => $from, 'to' => $to, 'text' => $text) = $email_data;

                    if ( !$text ) continue;

                    preg_match_all($regexp, $from.$to, $emails);

                    if ( empty($emails[0][0]) ) continue;

                    $email = mb_strtolower($emails[0][0], 'UTF-8');
                    $phones = Phone::searchByRegexp( $text );

                    if ( empty($phones[0]) ) continue;

                    foreach ($phones[0] as $phone) {

                        if ( !isset($this->parsed_data[ $email ]) || !in_array($phone, $this->parsed_data[ $email ]) ){
                            $this->parsed_data[ $email ][] = $phone;
                        }
                    }

                }

                //TODO: Можно удалить, это для дебагинга
                $this->cacheState();

                $nextPageToken = $messagesList->getNextPageToken();
                $params = [ 'pageToken' => $nextPageToken ];

            } while ( $nextPageToken );

        }

        //конец парсинга
        $name = sprintf("%s_%s.csv", $this->managerAlias, Carbon::now()->timestamp);
        $csv = new Csv(storage_path('app/public').'/'.$name );
        $csv->openStream();

        foreach ($this->parsed_data as $manager => $phones) {
            foreach ($phones as $phone) {
                $csv->insertRow([$manager, $phone]);
            }
        }

        $csv->closeStream();

        //Удаляем кеш файл
//        Storage::disk('local')->delete('public/parse-gmail/' . $this->managerAlias);
    }

    /**
     * Сериализует и сохраняет спарсенные данные
     */
    private function cacheState(): void
    {
        $path = storage_path('app/public/parse-gmail/') . $this->managerAlias;
        file_put_contents( $path, serialize($this->parsed_data) );
    }

    /**
     * Получает спарсенные данные из файла
     */
    private function getParsedData(): void
    {
        $path = storage_path('app/public/parse-gmail/') . $this->managerAlias;

        if ( file_exists( $path ) ) {

            $file_data = unserialize( file_get_contents( $path ) );
            if ( is_array($file_data) ) {
                $this->parsed_data = $file_data;
            }
        }
    }

    /**
     * Set to every given domain regexp for searching it and itself of 1 level up in mail text
     */
    private function setRegexps(): void
    {
        foreach ($this->domains as $domain) {
            $this->email_regexps[ $domain ] = "#(([a-zA-Z0-9_-]+\.)*[a-zA-Z0-9_-]+@([a-zA-Z0-9_-]+\.)?" . preg_quote($domain) . ")+#um";
        }
    }
}
