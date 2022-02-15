<?php

namespace App\Console\Commands;

use App\Helpers\Api\YMetrica;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class FetchingNewUrlReports extends Command

{
    public LoggerInterface $logger;
    public int $weeksInterval = 2;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'url-reports:fetch-new';

    /**
     * The console command description.
     */
    protected $description = 'That command fetching reports of URLs (Models/ObservableUrls.php) and save that data as Models/UrlViewsReport.php';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->logger = Log::build(['driver' => 'single', 'path' => storage_path('logs/commands/FetchingNewUrlViews.log') ]);
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->logger->info('starting');

        if ( !$last_fetch = Cache::get('FNUV_lastFetchedDate') ) {

            $last_fetch = (Carbon::now()->subDay())->toDateString();    //setup yesterday`s date like '2022-01-31' format
            Cache::put('FNUV_lastFetchedDate', $last_fetch );
        }

        $startDate = (Carbon::parse($last_fetch))->subWeeks( $this->weeksInterval )->toDateString();
        $this->logger->info('fetch from ' . $startDate . ' to ' . $last_fetch);

        try {
            YMetrica::pageReport( dateFrom: $startDate, dateTo: $last_fetch );

            Cache::put('FNUV_lastFetchedDate', $startDate );
            $this->logger->info('success');
        } catch ( GuzzleException $error ) {
            $this->logger->error( $error->getMessage() );
        }

        echo 'success';
    }
}
