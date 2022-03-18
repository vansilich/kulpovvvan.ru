<?php

namespace App\Console\Commands;

use App\Helpers\Csv;
use Carbon\Carbon;
use Illuminate\Console\Command;

class Tmp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmp:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {

        $PATH = 'C:\xampp\htdocs\kulpovvvan\storage\app\public\jobs\Gmail\PhonesByTriggers\cache\zam';
        $file_data = unserialize( file_get_contents( $PATH ) );
        $parsed_data = $file_data['parsed_data'];

        $name = sprintf("zam_%s.csv", Carbon::now()->timestamp);
        $csv = new Csv( storage_path("app/public/jobs/Gmail/PhonesByTriggers/$name") );
        $csv->openStream();

        //setup headers in file
        $csv->insertRow(['email', 'trigger', 'from', 'to', 'emails', 'phones']);

        foreach ($parsed_data as $email => $triggers) {
            foreach ( $triggers as $trigger => $values ) {
                foreach ($values as $value) {

                    $csv->insertRow([
                        $email,
                        $trigger,
                        $value['from'],
                        $value['to'],
                        $value['emails'] ? implode("\n", $value['emails']) : null,
                        $value['phones'] ? implode("\n", $value['phones']) : null,
                    ]);
                }
            }
        }

        $csv->closeStream();
    }
}
