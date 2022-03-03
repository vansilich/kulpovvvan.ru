<?php

namespace App\Helpers\Api;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;

class Comagic
{

    private string $api_key;
    public LoggerInterface $errorLogger;
    private string $api_endpoint = 'https://dataapi.comagic.ru/v2.0';

    public function __construct()
    {
        $this->api_key = config('services.comagic.key');
        $this->errorLogger = Log::build(['driver' => 'single', 'path' => storage_path('logs/api/comagic/error.log')]);
    }

    /**
     * @throws GuzzleException
     */
    public function visitorsCalls(string $date_from, string $date_till)
    {
        dd($this->api_key);
        $client = new Client([
            'headers' => [
                'charset' => "UTF-8",
            ]
        ]);

        $response = null;

        try {
            $response = $client->post( $this->api_endpoint, [
                RequestOptions::JSON => [
                    'id' => uniqid(),
                    "method" => "get.calls_report",
                    "jsonrpc" => "2.0",
                    'params' => [
                        'access_token' => $this->api_key,
                        'date_from' => $date_from . ' 00:00:00',
                        'date_till' => $date_till . ' 23:59:59',
                        "fields" => [
                            'visitor_id',
                            'contact_phone_number',
                            'utm_source',
                            'utm_medium',
                            'utm_term',
                            'utm_content',
                            'utm_campaign',
                            'eq_utm_source',
                            'eq_utm_medium',
                            'eq_utm_term',
                            'eq_utm_content',
                            'eq_utm_campaign',
                            'eq_utm_referrer',
                            'eq_utm_expid',
                        ]
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

        return $response;
    }

}
