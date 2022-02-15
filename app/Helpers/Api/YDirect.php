<?php

namespace App\Helpers\Api;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

class YDirect
{

    public static int $timestamp;
    public static int $status_ok = 200;     //Возвращается вместе с переданными данными
    public static int $status_created = 201;    //Если отчет поставлен в очередь
    public static int $status_accepted = 202;    //Если еще в очереди
    public static int $status_bad = 400;    //Если параметры некорректны

    public static string $csv_separator = ';';
    public static string $csv_enclosure = '"';

    /**
     * @throws Exception
     */
    public static function getReport( $startDate, $endDate ): string
    {
        $login = config('services.direct.DIRECT_API_LOGIN');
        $token = config('services.direct.DIRECT_API_KEY');

        self::$timestamp = Carbon::now()->timestamp;
        $report_name = self::$timestamp . '_REPORT';

        $url = 'https://api.direct.yandex.com/json/v5/reports';

        $client = new Client([
            'headers' => [
                'Authorization' => "Bearer " . $token,
                'Accept-Language' => "ru",
                'Client-Login' => $login,
                'processingMode' => "auto",
                "skipReportHeader" => "true",
                "skipReportSummary" => "true",
                "returnMoneyInMicros" => "false"
            ]
        ]);

        $params = [
            "params" => [
                "Goals" => [ "120654577", "52691263" ],
                "AttributionModels" => ["LSC"],
                "SelectionCriteria" => [
                    "DateFrom" => $startDate,
                    "DateTo" => $endDate,
                ],
                "FieldNames" => ["AdId", "AdGroupId", "Conversions", "CostPerConversion", "Ctr", "Clicks", "Impressions", "Date"],
                "ReportName" => $report_name,
                "ReportType" => "AD_PERFORMANCE_REPORT",
                "DateRangeType" => "CUSTOM_DATE",
                "Format" => "TSV",
                "IncludeVAT" => "YES",
            ]
        ];

        $data = ( self::getData($client, $url, $params) )->getBody()->getContents();
        $csv_path = storage_path('app/public')."/$report_name.csv";
        self::tsv_to_array($data, $csv_path);

        return "$report_name.csv";
    }

    /**
     * @throws Exception
     */
    public static function getData($client, $url, $params)
    {

        try {
            $response = $client->post( $url, [RequestOptions::JSON => $params] );

            if ( $response->getStatusCode() !== self::$status_ok ) {
                sleep(3);
                $response = self::getData($client, $url, $params);
            }

            return $response;

        } catch ( GuzzleException $exception ) {

            if ($exception->getCode() === self::$status_bad) {
                throw new Exception( 'Параметры некорректны' );
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function tsv_to_array( string $data_string, string $target_csv, $args = [] ): void
    {
        //key => default
        $fields = array(
            'header_row'=>true,
            'trim_headers'=>true, //trim whitespace around header row values
            'trim_values'=>true, //trim whitespace around all non-header row values
            'lb'=>"\n", //line break character
            'tab'=>"\t", //tab character
        );

        foreach ($fields as $key => $default) {
            $$key = array_key_exists($key, $args) ? $args[$key] : $default;
        }

        $lines = explode($lb, $data_string);

        $row = 0;
        $csv_stream = fopen( $target_csv, 'w+');

        foreach ($lines as $line) {
            $row++;

            $data[$row] = array();

            $values = explode($tab, $line);

            foreach ($values as $c => $value) {
                $data[$row][$c] = $value;
            }

            fputcsv( $csv_stream, $data[$row], self::$csv_separator, self::$csv_enclosure );
        }
        fclose($csv_stream);
    }

}
