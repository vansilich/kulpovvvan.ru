<?php

namespace App\Helpers\Api;

use \stdClass;
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
     * Запрос параметрезированного отчета у API
     *
     * @param string $api_method - Название метода для вызова у API
     * @param string $date_from - дата начала отчета
     * @param string $date_till - дата конца отчета
     * @param array $fields - массив полей, которые должны быть в отчете
     * @param array $filter - массив филтров
     */
    public function getReport(string $api_method, string $date_from, string $date_till, array $fields, array $filter = [] ): stdClass
    {
        $client = new Client([
            'headers' => [
                'Charset' => "UTF-8",
            ]
        ]);

        $response = null;
        $query = [
            'id' => uniqid(),
            "method" => $api_method,
            "jsonrpc" => "2.0",
            'params' => [
                'limit' => 10000,
                'access_token' => $this->api_key,
                'date_from' => $date_from . ' 00:00:00',
                'date_till' => $date_till . ' 23:59:59',
                "fields" => $fields
            ],
        ];

        if ( !empty($filter) ) {
            $query['params']['filter'] = $filter;
        }

        try {
            $response = $client->post( $this->api_endpoint, [RequestOptions::JSON => $query] );
        } catch ( GuzzleException $err ) {
            $this->errorLogger->error( $err->getMessage() );
        }

        $response = json_decode( $response->getBody()->getContents() );

        if ( isset( $response->error ) ) {
            $this->errorLogger->error( print_r($response->error->message, true) );
        }

        return $response;
    }

}
