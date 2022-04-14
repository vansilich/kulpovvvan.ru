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

    private array $params = [];
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
        $this->getPreviousState();

        if ( empty($this->email_regexps) ) {
            $this->setRegexps();
        }

        $this->gmail_api = new Gmail();
        $this->gmail_api->setClient( $this->managerAlias );
        $this->gmail_api->setupService();

        $this->messagesListIterator();

        //Удаляем кеш файл
        Storage::disk('local')->delete('public/jobs/Gmail/PhonesByTopDomains/cache/' . $this->managerAlias);
    }

    /**
     * @throws Exception
     */
    private function messagesListIterator(): void
    {

        foreach ($this->email_regexps as $domain => $regexp) {

            //Выставление критериев поиска писем
            $this->params['q'] = "from:(*@*$domain) OR to:(*@*$domain)";

            do {
                $messagesList = $this->gmail_api->queryMessages( $this->params );

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

                $nextPageToken = $messagesList->getNextPageToken();
                $this->params['pageToken'] = $nextPageToken;

                $this->cacheState();
            } while ( $nextPageToken );

            unset( $this->email_regexps[ $domain ] );
            $this->params = [];
        }

        //конец парсинга
        $name = sprintf("%s_%s.csv", $this->managerAlias, Carbon::now()->timestamp);
        $csv = new Csv( storage_path("app/jobs/Gmail/PhonesByTopDomains/$name") );
        $csv->openStream();

        foreach ($this->parsed_data as $manager => $phones) {
            foreach ($phones as $phone) {
                $csv->insertRow([$manager, $phone]);
            }
        }

        $csv->closeStream();
    }

    /**
     * Сериализует и сохраняет спарсенные данные
     */
    private function cacheState(): void
    {
        $path = storage_path('app/public/jobs/Gmail/PhonesByTopDomains/cache/') . $this->managerAlias;
        file_put_contents( $path, serialize([
            'parsed_data' => $this->parsed_data,
            'email_regexps' => $this->email_regexps,
            'pageToken' => $this->params['pageToken'] ?? null,
        ]) );
    }

    /**
     * Получает спарсенные данные из файла
     */
    private function getPreviousState(): void
    {
        $path = storage_path('app/public/jobs/Gmail/PhonesByTopDomains/cache/') . $this->managerAlias;

        if ( file_exists( $path ) ) {

            $file_data = unserialize( file_get_contents( $path ) );
            if ( is_array($file_data) ) {
                $this->parsed_data = $file_data['parsed_data'];
                $this->email_regexps = $file_data['email_regexps'];
                $this->params['pageToken'] = $file_data['pageToken'];
            }
        }
    }

    /**
     * Формирует для каждого переданного домена регулярное выражение, которое ищет совпадения
     * для почты с таким же доменом, либо доменом на 1 уровень выше
     */
    private function setRegexps(): void
    {
        foreach ($this->domains as $domain) {
            $this->email_regexps[ $domain ] = "#(([a-zA-Z0-9_-]+\.)*[a-zA-Z0-9_-]+@([a-zA-Z0-9_-]+\.)?" . preg_quote($domain) . ")+#um";
        }
    }
}
