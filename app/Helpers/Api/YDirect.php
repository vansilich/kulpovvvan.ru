<?php

namespace App\Helpers\Api;

use App\Helpers\Csv;
use App\Helpers\Tsv;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

class YDirect
{

    private string $apiEndpoint = 'https://api.direct.yandex.com/json/v5/reports';

    public int $status_ok = 200;            //Возвращается вместе с переданными данными
    public int $status_created = 201;       //Если отчет поставлен в очередь
    public int $status_accepted = 202;      //Если еще в очереди
    public int $status_bad = 400;           //Если параметры некорректны

    private Client $authHttpClient;

    public function __construct()
    {
        $this->setAuthHttpClint();
    }

    private function setAuthHttpClint(): void
    {
        $login = config('services.direct.DIRECT_API_LOGIN');
        $token = config('services.direct.DIRECT_API_KEY');

        $this->authHttpClient = new Client([
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
    }

    /**
     * Generate report name as timestamp
     */
    private function generateReportName(): string
    {
        return Carbon::now()->timestamp . '_REPORT';
    }

    /**
     * Fetch data from report even if it is compiling in offline mode
     *
     * @throws GuzzleException
     */
    public function getData( $params )
    {

        try {
            $response = $this->authHttpClient->post( $this->apiEndpoint, [RequestOptions::JSON => $params] );

            if ( $response->getStatusCode() !== $this->status_ok ) {
                sleep(3);
                $response = $this->getData( $params );
            }

            return $response;
        } catch ( GuzzleException $exception ) {

            if ($exception->getCode() === $this->status_bad) {
                throw $exception;
            }
        }
    }

    /**
     * Show statistic by goals 120654577 and 52691263
     *
     * @throws GuzzleException
     */
    public function emailTrackingAndCallReport( string $startDate, string $endDate ): string
    {
        $report_name = $this->generateReportName();

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

        $data = ( $this->getData( $params) )->getBody()->getContents();
        $resultCsv = new Csv( storage_path("app/public/$report_name.csv") );

        $resultCsv->openStream();
        foreach ( (new Tsv())->stringTsvToArrayIterator($data) as $row) {
            $resultCsv->insertRow( $row );
        }
        $resultCsv->closeStream();

        return "$report_name.csv";
    }

    /**
     * Show statistic by provided ad ids. Don`t show dates, when ads are stopped
     *
     * @throws GuzzleException
     */
    public function adsReportGroupByDay(string $startDate, string $endDate, array $adIds ): array
    {
        $report_name = $this->generateReportName();

        $params = [
            "params" => [
                "SelectionCriteria" => [
                    "DateFrom" => $startDate,
                    "DateTo" => $endDate,
                    "Filter" => [
                        [
                            "Field" => "AdId",
                            "Operator" => "IN",
                            "Values" => array_values($adIds)
                        ]
                    ]
                ],
                "FieldNames" => ["AdId", "CampaignId", "Date", "Ctr", "Impressions", "Clicks", "Cost"],
                "ReportName" => $report_name,
                "ReportType" => "AD_PERFORMANCE_REPORT",
                "DateRangeType" => "CUSTOM_DATE",
                "Format" => "TSV",
                "IncludeVAT" => "YES",
            ]
        ];


        $data = $this->getData( $params )->getBody()->getContents();
        $result = [];
        foreach ((new Tsv())->stringTsvToArrayIterator($data) as $row){
            $item = [];
            foreach ($params['params']['FieldNames'] as $key => $fieldName){
                $item[ $fieldName ] = $row[ $key ];
            }

            $result[] = $item;
        }
        return $result;
    }

}
