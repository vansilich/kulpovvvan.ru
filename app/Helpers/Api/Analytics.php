<?php

namespace App\Helpers\Api;

use Google_Client;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_Dimension;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_ReportRequest;
use Google_Service_AnalyticsReporting;

class Analytics
{

    /**
     * @throws \Google\Exception
     */
    public static function authorization(): Google_Service_AnalyticsReporting
    {
        // здесь нужно указать имя JSON-файла, содержащего сгенерированный ключ
        $KEY_FILE_LOCATION =  base_path() . '/credentials/google/credentials.json';

        $client = new Google_Client();
        $client->setApplicationName("Top pages");
        $client->setAuthConfig( $KEY_FILE_LOCATION );
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);

        return new Google_Service_AnalyticsReporting($client);
    }

    public static function getReport($analytics, $metric, $date_start, $date_end)
    {
        // здесь нужно указать значение своего VIEW_ID
        $VIEW_ID = "79933304";

        // создадим объект DateRange (диапазон дат)
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($date_start);
        $dateRange->setEndDate($date_end);

        $metricsValue = $metric['metrics'];
        $dimensionsValue = $metric['dimensions'];

        $metrics = [];
        foreach ($metricsValue as $name) {

            // создадим объект Metrics (показатели данных)
            $metrica = new Google_Service_AnalyticsReporting_Metric();
            // выражение показателя (в данном случае простое имя ga:pageviews – количество просмотров)
            $metrica->setExpression("ga:" . $name);
            // задаём альтернативное название для выражения показателя
            $metrica->setAlias($name);

            $metrics[] = $metrica;
        }

        $dimensions = [];
        if ($dimensionsValue) {

            foreach ($dimensionsValue as $name) {
                // создадим объект Dimensions (параметры данных в запросе)
                $dimension = new Google_Service_AnalyticsReporting_Dimension();

                // название параметра, по которому подбираются данные (в данном случае адрес страницы)
                $dimension->setName( 'ga:'.$name );
                $dimensions[] = $dimension;
            }

        }

        // создадим объект ReportRequest (запрос)
        $request = new Google_Service_AnalyticsReporting_ReportRequest();

        // добавим к запросу идентификатор viewId
        $request->setViewId( $VIEW_ID );

        // добавим к запросу максимальное количество строк, которое хотим получить
        $request->setPageSize("1000000000");

        // добавим к запросу диапазон дат
        $request->setDateRanges( $dateRange );

        // добавим к запросу метрики
        $request->setMetrics( $metrics );

        // добавим к запросу параметры
        if ($dimensionsValue)
            $request->setDimensions( $dimensions );

        // создадим объект GetReportsRequest
        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
        return $analytics->reports->batchGet($body);

    }

}
