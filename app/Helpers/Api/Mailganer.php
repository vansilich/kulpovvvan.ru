<?php

namespace App\Helpers\Api;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\GuzzleException;

class Mailganer
{

    public static string $api_breakpoint = 'https://mailganer.com/api/v2/';

    public static function unsubscribe($email): bool
    {
        $api_key = config('services.mailganer.key');
        $sources = config('services.mailganer.sources');

        $path = self::$api_breakpoint . "emails/unsubscribe/";

        foreach ($sources as $source) {
            $client = new Client([
                'headers' => [
                    'Authorization' => "CodeRequest ${api_key}",
                ]
            ]);

            try {
                $client->post( $path, [
                    RequestOptions::JSON => [
                        'email' => $email,
                        'source' => $source,
                    ]
                ]);
            } catch ( \Exception $err ) {
                if ($err->getCode() === 404) {
                    continue;
                }
                return false;
            }
        }

        return true;
    }

    public static function unsubscribeFromList( $email, $source )
    {
        $path = self::$api_breakpoint . "emails/unsubscribe/";
        $api_key = config('services.mailganer.key');

        $client = new Client([
            'headers' => [
                'Authorization' => "CodeRequest ${api_key}",
            ]
        ]);

        $client->post( $path, [
            RequestOptions::JSON => [
                'email' => $email,
                'source' => $source,
            ]
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public static function subscriberInfo( array $data )
    {
        $api_key = config('services.mailganer.key');

        $getReq = http_build_query($data);

        $path = self::$api_breakpoint . 'emails/?' . $getReq;

        $client = new Client([
            'headers' => [
                'Authorization' => "CodeRequest ${api_key}",
            ]
        ]);

        $response = $client->get( $path );

        return json_decode( $response->getBody()->getContents() );
    }

    /**
     * @throws GuzzleException
     */
    public static function subscribeToList($email, $source )
    {
        $api_key = config('services.mailganer.key');

        $path = self::$api_breakpoint . 'emails/';

        $client = new Client([
            'headers' => [
                'Authorization' => "CodeRequest ${api_key}",
            ]
        ]);

        $client->post( $path, [
            RequestOptions::JSON => [
                'email' => $email,
                'source' => $source,
                'not_doi' => true
            ]
        ]);
    }

}
