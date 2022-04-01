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
use Google\Service\Gmail\ListMessagesResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PhonesByTriggers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Cache $cache;
    private Gmail $gmailAPI;
    private ListMessagesResponse $messagesList;

    private string $managerMail;

    private string $triggersCacheKey;
    private string $pageTokenCacheKey;
    private string $messagesListCacheKey;

    private ?array $triggers = [];

    public function __construct(
        private string $managerAlias,
    ){
        $this->managerMail = Manager::where('nickname', $this->managerAlias)->first()->toArray()['mail'];

        $this->triggersCacheKey = $this->managerAlias . '_PhonesByTriggers_triggers';
        $this->pageTokenCacheKey = $this->managerAlias . '_PhonesByTriggers_pageToken';
        $this->messagesListCacheKey = $this->managerAlias . '_PhonesByTriggers_messagesList';
    }

    /**
     * @throws Exception
     */
    public function handle( Gmail $gmail, Cache $cache )
    {
        $this->cache = $cache;
        $this->triggers = $cache::get( $this->triggersCacheKey );

        if ( empty($this->triggers) ) {
            $this->setupTriggers();
        }

        $this->gmailAPI = $gmail;
        $this->gmailAPI->setClient( $this->managerAlias );
        $this->gmailAPI->setupService();

        $csv = $this->setupResultFile();
        $this->messagesListIterator( $csv );
        $csv->closeStream();

        $this->clearAllJobCache();
    }

    /**
     * Iterate gmails, cache state after every parsed chunk
     *
     * @throws Exception
     */
    private function messagesListIterator( Csv $csv )
    {

        foreach ($this->triggers as $key => $trigger) {

            $parsed_data = [];

            //Searching by trigger in subject
            $params = [
                'q' => "subject:$trigger",
                'pageToken' => $this->cache::get( $this->pageTokenCacheKey )
            ];

            do {
                /** @var ListMessagesResponse|null $cachedMessagesList */
                $cachedMessagesList = $this->cache::get( $this->messagesListCacheKey );
                $this->messagesList = $cachedMessagesList ?: $this->gmailAPI->queryMessages($params);

                foreach ( $this->gmailAPI->messagesTextIterator( $this->messagesList ) as $email_data) {

                    list('subject' => $subject, 'from' => $from, 'to' => $to, 'text' => $text) = $email_data;

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

                    $this->cacheRemainingMessagesList();
                }

                $nextPageToken = $this->messagesList->getNextPageToken();
                $params['pageToken'] = $nextPageToken;

                $this->cache::put( $this->pageTokenCacheKey, $nextPageToken );
                $this->cache::forget( $this->messagesListCacheKey );

            } while ( $nextPageToken );

            unset( $this->triggers[$key], $params );
            $this->cache::put( $this->triggersCacheKey, $this->triggers );

            if( !isset($parsed_data[$trigger]) ) {
                continue;
            }

            //saving results
            foreach ($parsed_data[$trigger] as $email => $values) {
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

    private function setupResultFile(): Csv
    {
        $name = sprintf("%s.csv", $this->managerAlias);
        $csv = new Csv( storage_path("app/public/jobs/Gmail/PhonesByTriggers/$name") );
        $file_exists = file_exists( $csv->filePath );

        $csv->openStream('a');

        //setup headers in file
        if ( !$file_exists ) {
            $csv->insertRow(['trigger', 'email', 'from', 'to', 'emails', 'phones']);
        }

        return $csv;
    }

    private function cacheRemainingMessagesList(): void
    {
        $messagesListArray = $this->messagesList->getMessages();
        array_shift($messagesListArray);
        $this->messagesList->setMessages( $messagesListArray );

        $this->cache::put( $this->messagesListCacheKey, $this->messagesList );
    }

    private function setupTriggers(): void
    {
        $triggers = new Csv( base_path('data/output.csv') );
        $triggers->openStream('r');

        while ( ($trigger = fgetcsv($triggers->stream)) !== false ) {

            $this->triggers[] = $trigger[0];
        }

        $triggers->closeStream();
    }

    private function clearAllJobCache(): void
    {
        $this->cache::forget($this->triggersCacheKey);
        $this->cache::forget($this->pageTokenCacheKey);
        $this->cache::forget($this->messagesListCacheKey);
    }

}
