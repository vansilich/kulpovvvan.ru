<?php

namespace App\Helpers\Api;

use Google\Service\AnalyticsReporting\MetricFilter;
use Google\Service\AnalyticsReporting\MetricFilterClause;
use Google_Client;
use Google\Exception;
use Google\Service\AnalyticsReporting;
use Google\Service\AnalyticsReporting\DateRange;
use Google\Service\AnalyticsReporting\Dimension;
use Google\Service\AnalyticsReporting\GetReportsRequest;
use Google\Service\AnalyticsReporting\Metric;
use Google\Service\AnalyticsReporting\ReportRequest;

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
        $KEY_FILE_LOCATION =  base_path() . '/credentials/google/credentials.json';

        $client = new Google_Client();
        $client->setApplicationName("Top pages");
        $client->setAuthConfig( $KEY_FILE_LOCATION );
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);

        $this->analyticsReporting = new AnalyticsReporting($client);
    }

    public function getReport( $metric, $date_start, $date_end )
    {

        /** @var Metric[] $metrics */
        $metrics = [];
        foreach ($metric['metrics'] as $name) {

            $metrica = new Metric();
            $metrica->setExpression("ga:" . $name);
            $metrica->setAlias($name);
            $metrics[] = $metrica;
        }

        /** @var Dimension[] $dimensions */
        $dimensions = [];
        if ( isset($metric['dimensions']) ) {

            foreach ($metric['dimensions'] as $name) {
                $dimension = new Dimension();
                $dimension->setName( 'ga:'.$name );
                $dimensions[] = $dimension;
            }
        }

        /** @var MetricFilterClause[] $metricFilterClauses */
        $metricFilterClauses = [];
        if ( isset($metric['metricFilterClauses']) ) {

            $filterClauses = new MetricFilterClause();
            foreach ($metric['metricFilterClauses'] as $filterClause){

                $filter = new MetricFilter;
                $filter->setMetricName($filterClause["metricName"]);
                $filter->setOperator("operator");
                $filter->setComparisonValue("comparisonValue");

                if ( isset($filterClause['not']) ) {
                    $filter->setNot($filterClause['not']);
                }

                $filterClauses->setFilters($filter);
            }

            $metricFilterClauses[] = $filterClauses;
        }

        $request = $this->initAnalyticsReportingRequest(
            $this->initDateRanges($date_start, $date_end),
            $metrics,
            $dimensions,
            $metricFilterClauses,
        );

        // создадим объект GetReportsRequest
        $body = new GetReportsRequest();
        $body->setReportRequests([ $request ]);
        return $this->analyticsReporting->reports->batchGet($body);
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
     *
     * @return ReportRequest
     */
    private function initAnalyticsReportingRequest(DateRange $dateRange, array $metrics = [], array $dimensions = [], array $metricFilters = []): ReportRequest
    {

        $request = new ReportRequest();

        $request->setViewId( $this->view_id );

        // добавим к запросу максимальное количество строк, которое хотим получить
        $request->setPageSize("1000000000");

        $request->setDateRanges( $dateRange );

        if ( !empty($metrics) ){
            $request->setMetrics( $metrics );
        }

        if ( !empty($dimensions) ) {
            $request->setDimensions( $dimensions );
        }

        if ( !empty($metricFilters) ) {
            $request->setMetricFilterClauses($metricFilters);
        }

        return $request;
    }

}
