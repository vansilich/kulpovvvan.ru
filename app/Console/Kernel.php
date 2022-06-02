<?php

namespace App\Console;

use App\Helpers\Api\Gmail;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $logger = Log::build(['driver' => 'single', 'path' => storage_path('logs/cron.log')]);

//        $schedule->command('url-reports:fetch-new')->daily();
        $schedule->command('queue:retry all')->everyFifteenMinutes();
        $schedule->command('queue:work --stop-when-empty');
        $schedule->command('queue:work --queue=mailganer --stop-when-empty');
        $schedule->command('queue:work --queue=gmail-fluid --stop-when-empty');

        $schedule->call( function () use ($logger) {
            $gmailAPI = new Gmail();
            $gmailAPI->setClient('mail');
            $gmailAPI->setupService();

            $response = $gmailAPI->startWatch('projects/peak-age-279206/topics/newInboxTrigger');

            $logger->debug("Вызвано startWatch() для пользователя mail.\n" . serialize($response));
        })->everySixHours();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
