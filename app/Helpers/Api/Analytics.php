<?php

namespace App\Helpers\Api;

use Carbon\Carbon;
use Google\Client;
use Google\Exception;
use Google\Service\AnalyticsReporting;
use Google\Service\AnalyticsReporting\DimensionFilter;
use Google\Service\AnalyticsReporting\DimensionFilterClause;
use Google\Service\AnalyticsReporting\GetReportsResponse;
use Google\Service\AnalyticsReporting\Metric;
use Google\Service\AnalyticsReporting\DateRange;
use Google\Service\AnalyticsReporting\Dimension;
use Google\Service\AnalyticsReporting\MetricFilter;
use Google\Service\AnalyticsReporting\ReportRequest;
use Google\Service\AnalyticsReporting\GetReportsRequest;
use Google\Service\AnalyticsReporting\MetricFilterClause;
use Google\Service\AnalyticsReporting\ReportRow;

class Analytics
{

    private string $view_id = '79933304';
    private AnalyticsReporting $analyticsReporting;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->authorization();
    }

    /**
     * @throws Exception
     */
    public function authorization(): void
    {
        // здесь нужно указать имя JSON-файла, содержащего сгенерированный ключ
        $KEY_FILE_LOCATION =  base_path('/credentials/google/credentials.json');

        $client = new Client();
        $client->setApplicationName("Top pages");
        $client->setAuthConfig( $KEY_FILE_LOCATION );
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);

        $this->analyticsReporting = new AnalyticsReporting($client);
    }

    public function customReport( $requestData, $date_start, $date_end ): GetReportsResponse
    {
        $metrics                = isset($requestData['metrics']) ? $this->initMetrics($requestData) : [];
        $dimensions             = isset($requestData['dimensions']) ? $this->initDimensions( $requestData ) : [];
        $metricFilterClauses    = isset($requestData['metricFilterClauses']) ? $this->initMetricFilterClauses( $requestData ) : [];
        $dimensionFilterClauses = isset($requestData['dimensionFilterClauses']) ? $this->initDimensionFilterClauses( $requestData ) : [];

        $request = $this->initAnalyticsReportingRequest(
            $this->initDateRanges($date_start, $date_end),
            $metrics,
            $dimensions,
            $metricFilterClauses,
            $dimensionFilterClauses,
        );

        // создадим объект GetReportsRequest
        $body = new GetReportsRequest();
        $body->setReportRequests([ $request ]);
        return $this->analyticsReporting->reports->batchGet($body);
    }

    /**
     * @throws \Google\Service\Exception
     */
    public function adsReportGroupByDay(string $date_start, string $date_end, array $adsIds = []): array
    {
        $metricsNames = [ 'ga:CTR', 'ga:impressions', 'ga:adClicks', 'ga:adCost' ];
        $metrics = $this->initMetrics( $metricsNames );

        $dimensionsNames = ['ga:adwordsCreativeID', 'ga:adwordsCampaignID', 'ga:date'];
        $dimensions = $this->initDimensions( $dimensionsNames );

        $dimensionFilterClauses = !empty($adsIds)
            ? $this->initDimensionFilterClauses( [[
                'dimensionName' => 'ga:adwordsCreativeID',
                'operator' => 'IN_LIST',
                'expressions' => array_values($adsIds) ]] )
            : [];

        $request = $this->initAnalyticsReportingRequest(
            dateRange: $this->initDateRanges($date_start, $date_end),
            metrics: $metrics,
            dimensions: $dimensions,
            dimensionFilters: $dimensionFilterClauses,
        );

        $body = new GetReportsRequest();
        $body->setReportRequests([ $request ]);

        $response = $this->analyticsReporting->reports->batchGet($body);
        $responseRows = $response[0]->getData()->getRows();

        return $this->responseRowsToArr($responseRows, $dimensionsNames, $metricsNames);
    }

    private function initDateRanges( string $date_start, string $date_end ): DateRange
    {
        $dateRange = new DateRange();
        $dateRange->setStartDate($date_start);
        $dateRange->setEndDate($date_end);

        return $dateRange;
    }

    /**
     * @param DateRange $dateRange
     * @param Metric[] $metrics
     * @param Dimension[] $dimensions
     * @param MetricFilterClause[] $metricFilters
     * @param DimensionFilterClause[] $dimensionFilters
     *
     * @return ReportRequest
     */
    private function initAnalyticsReportingRequest(
        DateRange $dateRange,
        array $metrics,
        array $dimensions = [],
        array $metricFilters = [],
        array $dimensionFilters = []
    ): ReportRequest
    {

        $request = new ReportRequest();
        $request->setViewId( $this->view_id );
        // добавим к запросу максимальное количество строк, которое хотим получить
        $request->setPageSize("1000000000");
        $request->setDateRanges( $dateRange );

        $request->setMetrics( $metrics );


        if ( !empty($dimensions) ) {
            $request->setDimensions( $dimensions );
        }

        if ( !empty($metricFilters) ) {
            $request->setMetricFilterClauses($metricFilters);
        }

        if ( !empty($dimensionFilters) ) {
            $request->setDimensionFilterClauses($dimensionFilters);
        }

        return $request;
    }

    /**
     * @param array $metricFilterClauses
     * @return MetricFilterClause[]
     */
    private function initMetricFilterClauses( array $metricFilterClauses ): array
    {
        /** @var MetricFilterClause[] $request_metricFilterClauses */
        $request_metricFilterClauses = [];

        $filterClauses = new MetricFilterClause();
        foreach ($metricFilterClauses as $filterClause){

            $filter = new MetricFilter;
            $filter->setMetricName($filterClause["metricName"]);
            $filter->setOperator($filterClause["operator"]);
            $filter->setComparisonValue("comparisonValue");

            if ( isset($filterClause['not']) ) {
                $filter->setNot($filterClause['not']);
            }

            $filterClauses->setFilters($filter);
        }

        $request_metricFilterClauses[] = $filterClauses;

        return $request_metricFilterClauses;
    }

    /**
     * @param array $dimensionFilterClauses
     * @return DimensionFilterClause[]
     */
    private function initDimensionFilterClauses( array $dimensionFilterClauses ): array
    {

        $filterClauses = new DimensionFilterClause();
        foreach ($dimensionFilterClauses as $filterClause){

            $filter = new DimensionFilter();
            $filter->setDimensionName($filterClause["dimensionName"]);
            $filter->setOperator($filterClause["operator"]);
            $filter->setExpressions($filterClause["expressions"]);

            if ( isset($filterClause['not']) ) {
                $filter->setNot($filterClause['not']);
            }

            $filterClauses->setFilters($filter);
        }

        return [$filterClauses];
    }

    /**
     * @param array $dimensions
     * @return Dimension[]
     */
    private function initDimensions( array $dimensions ): array
    {
        /** @var Dimension[] $request_dimensions */
        $request_dimensions = [];

        foreach ($dimensions as $name) {

            $dimension = new Dimension();
            $dimension->setName( $name );
            $request_dimensions[] = $dimension;
        }

        return $request_dimensions;
    }

    /**
     * @param array $metrics
     * @return Metric[]
     */
    private function initMetrics( array $metrics ): array
    {
        /** @var Metric[] $request_metrics */
        $request_metrics = [];

        foreach ($metrics as $metric) {

            $request_metric = new Metric();
            $request_metric->setExpression( $metric );
            $request_metrics[] = $request_metric;
        }

        return $request_metrics;
    }

    /**
     * @param ReportRow[] $rows
     * @param string[] $dimensionsNames
     * @param string[] $metricsNames
     */
    private function responseRowsToArr( array $rows, array $dimensionsNames, array $metricsNames ): array
    {
        $result = [];
        for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++){

            $row = $rows[$rowIndex];
            $item = [];

            foreach ($row->getDimensions() as $key => $dimension){
                if ( $dimensionsNames[$key] === 'ga:date' ){
                    $dimension = Carbon::createFromFormat('Ymd', $dimension)->format('Y.m.d');
                }
                $item[ $dimensionsNames[$key] ] = $dimension;
            }

            foreach ($row->getMetrics()[0]->getValues() as $key => $metric) {
                $item[ $metricsNames[$key] ] = $metric;
            }

            $result[] = $item;
        }

        return $result;
    }

}
