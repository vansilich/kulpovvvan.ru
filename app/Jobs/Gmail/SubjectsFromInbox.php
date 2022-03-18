<?php

namespace App\Jobs\Gmail;

use App\Helpers\Api\Gmail;
use App\Helpers\Csv;
use App\Helpers\Email;
use App\Helpers\Phone;
use App\Models\Manager;
use Exception;
use Generator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SubjectsFromInbox implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $cacheFile;
    private string $managerMail;

    private Gmail $gmail_api;
    private array $params;

    public function __construct(
        private string $managerAlias,
    ) {
        $this->managerMail = Manager::where('nickname', $this->managerAlias)->first()->toArray()['mail'];
        $this->cacheFile = storage_path("app/public/jobs/Gmail/SubjectsFromInbox/cache/" . $this->managerAlias);
    }

    /**
     * @throws Exception
     */
    public function handle(Gmail $gmail )
    {
        $this->getParsedData();

        $this->gmail_api = $gmail;
        $this->gmail_api->setClient( $this->managerAlias );
        $this->gmail_api->setupService();

        $name = sprintf("%s.csv", $this->managerAlias);
        $csv = new Csv( storage_path("app/public/jobs/Gmail/SubjectsFromInbox/$name") );
        $file_exists = file_exists( $csv->filePath );

        $csv->openStream('a');

        //setup headers in file
        if ( !$file_exists ) {
            $csv->insertRow(['trigger', 'subject', 'email', 'from', 'to', 'emails', 'phones']); //TODO исправить заголовки
        }

        $this->messagesListIterator( $csv );

        $csv->closeStream();

        //clear cache
        Storage::disk('local')->delete("public/jobs/Gmail/SubjectsFromInbox/cache/$this->managerAlias");
    }

    /**
     * @throws Exception
     */
    public function messagesListIterator( Csv $csv ): void
    {

        //Searching all emails by all time
        $this->params['q'] = "";

        do {
            $messagesList = $this->gmail_api->queryMessages($this->params);

            foreach ( $this->gmail_api->messagesTextIterator( $messagesList ) as $email_data ) {

                list( 'subject' => $subject, 'from' => $from, 'to' => $to, 'text' => $text) = $email_data;

                $externalEmail = preg_match( '#'.preg_quote($this->managerMail).'#u', $from) ? $to : $from;
                $email = Email::searchByRegexp( $externalEmail );

                if ( empty($email[0]) ) {
                    continue;
                }

                $email = mb_strtolower($email[0][0], 'UTF-8');

                $phones = $this->phonesToUniqueArray( $text );
                $emails = $this->emailsToUniqueArray( $text );

                $trigger = $this->matchTrigger( $subject );

                $csv->insertRow([
                    $trigger,
                    $subject,
                    $email,
                    $from,
                    $to,
                    $emails ? implode("\n", $emails) : null,
                    $phones ? implode("\n", $phones) : null,
                ]);
            }

            $nextPageToken = $messagesList->getNextPageToken();
            $this->params['pageToken'] = $nextPageToken;

            $this->cacheState();
        } while ( $nextPageToken );

    }

    private function matchTrigger( string $mailSubject ): ?string
    {
        $regexp = "#\#[a-z0-9]+#i";

        preg_match( $regexp, $mailSubject, $trigger );
        return $trigger[0] ?? null;
    }

    /**
     * Serialize parsed data
     */
    private function cacheState(): void
    {
        file_put_contents( $this->cacheFile, serialize([
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
                $this->params['pageToken'] = $file_data['pageToken'];
            }
        }
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
