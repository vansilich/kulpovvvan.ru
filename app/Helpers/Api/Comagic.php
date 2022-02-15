<?php

namespace App\Helpers\Api;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;

class Comagic
{

    public string $api_breakpoint;
    public LoggerInterface $errorLogger;

    public function __construct()
    {
        $this->api_breakpoint = 'https://dataapi.comagic.ru/v2.0';
        $this->errorLogger = Log::build(['driver' => 'single', 'path' => storage_path('logs/api/comagic/error.log')]);
    }

    /**
     * @throws GuzzleException
     */
    public function callInfo(string $date_from, string $date_till)
    {
        $api_key = config('services.comagic.key');

        $client = new Client([
            'headers' => [
                'charset' => "UTF-8",
            ]
        ]);

        $response = null;

        try {
            $response = $client->post( $this->api_breakpoint, [
                RequestOptions::JSON => [
                    'id' => uniqid(),
                    "method" => "get.calls_report",
                    "jsonrpc" => "2.0",
                    'params' => [
                        'access_token' => $api_key,
                        'date_from' => $date_from . ' 00:00:00',
                        'date_till' => $date_till . ' 23:59:59',
                        "fields" => ['visitor_id', 'contact_phone_number']
                    ],
                ]
            ]);
        } catch (GuzzleException $err) {

            $this->errorLogger->error( $err->getMessage() );
        }

        $response = json_decode( $response->getBody()->getContents() );

        if ( isset( $response->error ) ) {
            $this->errorLogger->error( print_r($response, true) );
        }

        return  $response;
    }

}
