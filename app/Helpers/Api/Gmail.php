<?php

namespace App\Helpers\Api;

use Exception;
use Generator;
use Carbon\Carbon;
use Google_Client;
use Google_Service_Gmail;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Facades\Log;
use Google\Service\Gmail\MessagePart;
use Illuminate\Support\Facades\Cache;
use Google\Service\Gmail\ListMessagesResponse;

class Gmail
{

    public Google_Client $client;
    private LoggerInterface $logger;
    public Google_Service_Gmail $service;

    /**
     * @throws Exception
     */
    public function __construct()
    {
       $this->logger = Log::build(['driver' => 'single', 'path' => storage_path('logs/api/gmail/runtime.log') ]);
    }

    public function setupService(): void
    {
        $this->service = new Google_Service_Gmail($this->client);
    }

    /**
     * @param array $params
     * @return ListMessagesResponse
     * @throws Exception
     */
    public function queryMessages( array $params = [] ): ListMessagesResponse
    {
        $params = array_merge( [ 'maxResults' => 500 ], $params );

        return $this->service->users_messages->listUsersMessages('me', $params);
    }

    /**
     * Iterate list of messages and yield from, to addresses and text/plain part from it
     *
     * @param ListMessagesResponse $list
     * @return Generator
     * @throws Exception
     */
    public function messagesTextIterator( ListMessagesResponse $list ): Generator
    {

        foreach ($list->getMessages() as $message) {
            $message_id = $message->id;

            //sometimes return '401 unauthorized' error
            $payload = $this->service->users_messages->get('me', $message_id, ['format' => 'full'])->getPayload();

            $headers = $payload->getHeaders();
            $from = $this->getHeader($headers, 'From');
            $to = $this->getHeader($headers, 'To');
            $subject = $this->getHeader( $headers, 'Subject' );

            /*
             * In $payload we have parts property - array of parts. First part - main body. Usually email has only one part.
             * But if '---------- Forwarded message ----------' text presented in body,
             * it is stored in other parts. And we need to implode them to main text in 1st part.
             */
            $text = '';
            foreach ( $payload->getParts() as $part ) {
                $text .= $this->getEmailText($part);
            }

            yield [
                'subject' => $subject,
                'from' => $from,
                'to' => $to,
                'text' => $text,
            ];
        }
    }

    /**
     * Return timestamp of email send date
     *
     * @param string $dateFromHeader
     * @return bool|int
     */
    private function getMailTimestamp( string $dateFromHeader ): bool|int
    {
        $is_date = preg_match('#(\w{3},\s+)?\d+ \w+ \d+ \d+:\d+:\d+#', $dateFromHeader, $matches);

        if ( $is_date ) {
            return (int) ( Carbon::parse( $matches[0] ))->timestamp;
        }

        return false;
    }

    /**
     * Return email`s header value
     *
     * @param array $headers
     * @param string $name
     * @return string|bool
     */
    public function getHeader( array $headers, string $name ): string|bool
    {
        foreach ($headers as $header) {
            if ($header['name'] === $name) {
                return $header['value'];
            }
        }
        return false;
    }

    /**
     * Retrieve 'text/plain' part from message
     *
     * @param MessagePart $messagePart
     * @return string|bool
     */
    public function getEmailText( MessagePart $messagePart ): string|bool
    {

        // 'text/html' for more correct parsing. 'text/plain' parts sometimes incorrectly imploding words
        if ($messagePart->mimeType !== 'text/html') {

            if ( $messagePart->parts ) {
                foreach ($messagePart->getParts() as $part) {

                    //'$maybeText' because it can be string with text or 'false', if text not found
                    $maybeText = $this->getEmailText( $part );
                    if ( $maybeText ) return $maybeText;
                }
            }
            else {
                return false;
            }
        }

        return decodeGmailBody( $messagePart['body']->data );
    }

    /**
     * @throws \Google\Exception
     */
    public function setupAuth(): Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('Gmail API PHP Quickstart');
        $client->setScopes(Google_Service_Gmail::GMAIL_READONLY);
        $client->setAuthConfig(base_path() . '/credentials/google/gmail/credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        return $client;
    }

    /**
     * @throws Exception
     */
    public function setClient( string $clientName ): void
    {
        $client = $this->setupAuth();

        $tokenPath = base_path() . "/credentials/google/gmail/managers/${clientName}.json";

        if ( file_exists($tokenPath) ) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            }
            else {
                throw new Exception("Аккаунт с именем $clientName не залогинен. Обновитие токен через artisan команду ", 500);
            }
            // Save the token to a file.
            if ( !file_exists( dirname($tokenPath)) ) {
                mkdir( dirname($tokenPath), 0700, true);
            }
            file_put_contents( $tokenPath, json_encode($client->getAccessToken()) );
        }

        $this->client = $client;
    }

}
