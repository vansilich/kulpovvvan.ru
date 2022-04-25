<?php

namespace App\Helpers\Api;

use App\Helpers\Email;
use Exception;
use Generator;
use Google\Service\Exception as GoogleException;
use Google\Service\Gmail\ListHistoryResponse;
use Google\Service\Gmail\Message;
use Google\Service\Gmail\MessagePartHeader;
use Google_Client;
use Google_Service_Gmail;
use Google\Service\Gmail\MessagePart;
use Google\Service\Gmail\ListMessagesResponse;

class Gmail
{

    public Google_Client $client;
    public Google_Service_Gmail $service;

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
            $message = $this->service->users_messages->get('me', $message_id, ['format' => 'full']);
            $payload = $message->getPayload();

            $headers = $payload->getHeaders();

            $from = $this->getHeader($headers, 'From');
            $to = $this->getHeader($headers, 'To');
            $subject = $this->getHeader( $headers, 'Subject' );
            $timestamp = $message->getInternalDate();

            /*
             * In $payload we have parts property - array of parts. First part - main body. Usually email has only one part.
             * But if '---------- Forwarded message ----------' text presented in body,
             * it is stored in other parts. And we need to include them to main text in 1st part.
             */
            $text = '';
            foreach ( $payload->getParts() as $part ) {
                $text .= $this->getEmailText($part);
            }

            $fileNames = [];
            $this->getEmailAttachmentsNames($payload, $fileNames);

            yield [
                'subject' => $subject,
                'from' => $from,
                'to' => $to,
                'text' => $text,
                'timestamp' => $timestamp,
                'attachmentsNames' => $fileNames
            ];
        }
    }

    public function historyList( array $params ): ListHistoryResponse
    {
        return $this->service->users_history->listUsersHistory('me', $params);
    }

    public function messageById( string $id, array $opt_params = [] ): Message|false
    {
        $params = array_merge( ['format' => 'full'], $opt_params);
        try {
            $message = $this->service->users_messages->get( 'me', $id, $params);
        } catch (GoogleException $exception) {
            //message not found
            return false;
        }

        return $message;
    }

    /**
     * Return email`s header value
     *
     * @param MessagePartHeader[] $headers
     * @param string $name
     * @return string|false
     */
    public function getHeader( array $headers, string $name ): string|false
    {
        foreach ($headers as $header) {
            if ($header['name'] === $name) {
                return $header['value'];
            }
        }
        return false;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return MessagePartHeader
     */
    public function createHeader( string $key, mixed $value ): MessagePartHeader
    {
        $header = new MessagePartHeader();
        $header->setName($key);
        $header->setValue($value);

        return $header;
    }

    /**
     * @param MessagePartHeader[] $headers
     * @return string|false
     */
    public function getFromAddress( array $headers ): string|false
    {
        $from = $this->getHeader( $headers, 'From' );
        $matches = Email::searchByRegexp( $from );
        return !empty($matches[0])
            ? $matches[0][0]
            : false;
    }

    /**
     * @param MessagePartHeader[] $headers
     * @return string|false
     */
    public function getToAddress( array $headers ): string|false
    {
        $to = $this->getHeader( $headers, 'To' );
        $matches = Email::searchByRegexp( $to );
        return !empty($matches[0])
            ? $matches[0][0]
            : false;
    }

    /**
     * Retrieve 'text/plain' part from message
     */
    public function getEmailText( MessagePart $messagePart ): string|bool
    {

        // 'text/html' for more correct parsing. 'text/plain' parts sometimes incorrectly imploding words
        if ($messagePart->mimeType !== 'text/html') {

            if ( isset($messagePart->parts) ) {
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

    private function getEmailAttachmentsNames(MessagePart $messagePart, array &$fileNames): void
    {

        if ( isset($messagePart->parts) ) {
            foreach ($messagePart->getParts() as $part) {

                $this->getEmailAttachmentsNames( $part, $fileNames );
            }
        }

        if ($messagePart->filename !== '') {
            $fileNames[] = $messagePart->filename;
        }
    }

    /**
     * @throws \Google\Exception
     */
    public function setupAuth(): Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('Gmail API PHP Quickstart');
        $client->setScopes(Google_Service_Gmail::GMAIL_MODIFY);
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
            if ( !file_exists(dirname($tokenPath)) ) {
                mkdir( dirname($tokenPath), 0700, true);
            }
            file_put_contents( $tokenPath, json_encode($client->getAccessToken()) );
        }

        $this->client = $client;
    }

}
