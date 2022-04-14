<?php

namespace App\Jobs\Gmail;

use App\Helpers\Api\Gmail;
use App\Helpers\Csv;
use App\Helpers\Email;
use App\Helpers\Phone;
use App\Models\Manager;
use Carbon\Carbon;
use Exception;
use Google\Service\Gmail\ListMessagesResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class SubjectsFromInbox implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $managerMail;
    private string $pageTokenCacheKey;
    private string $messagesListCacheKey;

    private ListMessagesResponse $messagesList;

    private Gmail $gmailAPI;

    public function __construct(
        private string $managerAlias,
    ){
        $this->managerMail = Manager::where('nickname', $this->managerAlias)->first()->toArray()['mail'];

        $this->pageTokenCacheKey = $this->managerAlias . '_SubjectsFromInbox_pageToken';
        $this->messagesListCacheKey = $this->managerAlias . '_SubjectsFromInbox_messagesList';
    }

    /**
     * @throws Exception
     */
    public function handle( Gmail $gmail, Cache $cache )
    {
        $this->gmailAPI = $gmail;
        $this->gmailAPI->setClient( $this->managerAlias );
        $this->gmailAPI->setupService();

        $name = sprintf("%s.csv", $this->managerAlias);
        $csv = new Csv( storage_path("app/public/jobs/Gmail/SubjectsFromInbox/$name") );
        $file_exists = file_exists( $csv->filePath );

        $csv->openStream('a');

        //setup headers in file
        if ( !$file_exists ) {
            $csv->insertRow(['trigger', 'subject', 'email', 'from', 'to', 'emails', 'phones', 'date']);
        }

        $this->messagesListIterator( $csv, $cache );

        $csv->closeStream();

        //clear cache
        $cache::forget( $this->messagesListCacheKey );
        $cache::forget( $this->pageTokenCacheKey );
    }

    /**
     * @throws Exception
     */
    public function messagesListIterator( Csv $csv, Cache $cache ): void
    {
        //Searching all emails by all time
        $params = [
            "q" => '',
            'pageToken' => $cache::get( $this->pageTokenCacheKey )
        ];

        do {

            /** @var ListMessagesResponse|null $cachedMessagesList */
            $cachedMessagesList = $cache::get( $this->messagesListCacheKey );
            $this->messagesList = $cachedMessagesList ?: $this->gmailAPI->queryMessages($params);

            foreach ( $this->gmailAPI->messagesTextIterator( $this->messagesList ) as $email_data ) {

                $clientEmail = preg_match( '#'.preg_quote($this->managerMail).'#u', $email_data['from'])
                    ? $email_data['to']
                    : $email_data['from'];
                $email = Email::searchByRegexp( $clientEmail );

                if ( empty($email[0]) ) {
                    continue;
                }

                $email = mb_strtolower($email[0][0], 'UTF-8');

                $dateTime = Carbon::createFromTimestampMs($email_data['timestamp'])->toDateTimeString();
                $phones = $this->phonesToUniqueArray( $email_data['text'] );
                $emails = $this->emailsToUniqueArray( $email_data['text'] );
                $trigger = $this->matchTriggerInSubject( $email_data['subject'] );

                $csv->insertRow([
                    $trigger,
                    $email_data['subject'],
                    $email,
                    $email_data['from'],
                    $email_data['to'],
                    $emails ? implode("\n", $emails) : null,
                    $phones ? implode("\n", $phones) : null,
                    $dateTime
                ]);

                $this->cacheRemainingMessagesList( $cache );
            }

            $nextPageToken = $this->messagesList->getNextPageToken();
            $params['pageToken'] = $nextPageToken;

            $cache::put( $this->pageTokenCacheKey, $nextPageToken );
            $cache::forget( $this->messagesListCacheKey );

        } while ( $nextPageToken );

    }

    private function cacheRemainingMessagesList( Cache $cache ): void
    {
        $messagesListArray = $this->messagesList->getMessages();
        array_shift($messagesListArray);
        $this->messagesList->setMessages( $messagesListArray );

        $cache::put( $this->messagesListCacheKey, $this->messagesList );
    }

    private function matchTriggerInSubject( string $mailSubject ): ?string
    {
        $regexp = "#\#[a-z0-9]+#i";

        preg_match( $regexp, $mailSubject, $trigger );
        return $trigger[0] ?? null;
    }

    private function phonesToUniqueArray( string $mailText ): ?array
    {
        $phones = Phone::searchByRegexp( $mailText );

        return $phones[0] ? array_unique($phones[0]) : null;
    }

    private function emailsToUniqueArray( string $mailText ): ?array
    {
        $emails = Email::searchByRegexp( $mailText );

        return $emails[0] ? array_unique($emails[0]) : null;
    }

}
