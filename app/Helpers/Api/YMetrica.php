<?php

namespace App\Helpers\Api;

use App\Models\ObservableUrl;
use App\Models\UrlViewsReport;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Collection;

class YMetrica
{

    /**
     * Request to get the number of visits to certain pages on arbitrary dates.
     * $viewsReports grouped by week.
     *
     * @throws GuzzleException
     */
    public static function pageReport( $dateFrom, $dateTo ): void
    {
        $token = config('services.metrika.METRIKA_API_KEY');

        $client = new Client([
            'headers' => [
                'Authorization' => "OAuth " . $token,
                'Accept-Language' => "ru",
            ]
        ]);

        $urls = ObservableUrl::all();

        foreach ($urls as $observableUrl) {

            $api_url = self::pageReportUrl( $dateFrom, $dateTo, $observableUrl->url );
            $response = $client->get( $api_url );
            $response = json_decode( $response->getBody()->getContents() );

            foreach ($response->time_intervals as $key => $value) {

                $currentReportInterval = [
                    'url_id' => $observableUrl->id,
                    'day' => $value[0],
                    'views' => $response->totals[0][$key],
                ];

                $currentDate = Carbon::now()->toDate()->format('Y-m-d');

                //if requested present day - do not save it, because data can change until the end of the day
                if ( $currentReportInterval['day'] !== $currentDate ) {

                    UrlViewsReport::updateOrCreate([
                        'url_id' => $currentReportInterval['url_id'],
                        'day' => $currentReportInterval['day'],
                        ],
                        [
                            'views' => $currentReportInterval['views']
                        ]
                    );
                }
            }

        }

    }

    public static function pageReportUrl( $dateFrom, $dateTo, $url ): string
    {
        $query = [
            'ids' => '5484148',
            'metrics' => 'ym:s:pageviews',
            'group' => 'day',
            'date1' => $dateFrom,
            'date2' => $dateTo,
            'filters' => "ym:pv:URL=='". trim( $url ) ."'",
        ];

        return 'https://api-metrika.yandex.net/stat/v1/data/bytime?'. urldecode( http_build_query($query) );
    }

    /**
     * Get intervals what should be requested from api. Avoiding already existing in database intervals
     */
    public static function getIntervals( Collection $savedRanges, Carbon $dateFrom, Carbon $dateTo ): array
    {

        $requestingIntervals = [];
        foreach ($savedRanges as $interval) {

            $from = $dateFrom;
            $day = new Carbon( $interval->day );

            if ( $from->diffInDays($day) > 0 ) {

                $requestingIntervals[] = [
                    'from' => $from,
                    'to' => $day->subDay(),
                ];
            }

            $dateFrom = (new Carbon($interval->day))->addDay();
        }

        if ( $dateFrom->subDay()->diffInDays($dateTo) > 0 )
            $requestingIntervals[] = [
                'from' => $dateFrom->addDay(),
                'to' => $dateTo,
            ];

        return $requestingIntervals;
    }

}
