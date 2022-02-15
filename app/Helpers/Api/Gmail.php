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

    private LoggerInterface $logger;
    public Google_Client $client;
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
     * Iterate list of messages and yield text from it
     *
     * @param ListMessagesResponse $list
     * @return Generator
     * @throws Exception
     */
    public function messagesTextIterator( ListMessagesResponse $list ): Generator
    {

        foreach ($list->getMessages() as $message) {
            $message_id = $message->id;

            $payload = $this->service->users_messages->get('me', $message_id, ['format' => 'full'])->getPayload();

            $headers = $payload->getHeaders();
            $from = $this->getHeader($headers, 'From');
            $to = $this->getHeader($headers, 'To');

            $text = $this->getEmailText($payload);

            if ( !$text ) {
                $this->logger->error("getEmailText: Сообщение с id $message_id оказалось без текста");
            }

            yield [
                'from' => $from,
                'to' => $to,
                'text' => $text,
            ];
        }
    }

    private function getMailTimestamp( string $dateFromHeader ): bool|int
    {
        $is_date = preg_match('#(\w{3},\s+)?\d+ \w+ \d+ \d+:\d+:\d+#', $dateFromHeader, $matches);

        if ( $is_date ) {
            return (int) ( Carbon::parse( $matches[0] ))->timestamp;
        }

        return false;
    }

    public function getHeader( $headers, $name ): string|bool
    {
        foreach ($headers as $header) {
            if ($header['name'] === $name) {
                return $header['value'];
            }
        }
        return false;
    }

    /**
     * Retrieve text from 'text/plain' part from message
     *
     * @param MessagePart $messagePart
     * @return string|bool
     */
    public function getEmailText( MessagePart $messagePart ): string|bool
    {

        if ($messagePart->mimeType !== 'text/plain') {

            if ( $messagePart->parts ) {
                foreach ($messagePart->getParts() as $part) {

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
