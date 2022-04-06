<?php

namespace App\Console\Commands;

use App\Helpers\Csv;
use App\Helpers\Email;
use Illuminate\Console\Command;

class DeleteEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:emails';

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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $manager = 'tilov';
        $resultCsv = new Csv(base_path("data/${manager}_result.csv"));
        $resultCsv->openStream();

        $dataCsv = new Csv(base_path("data/$manager.csv"));
        $dataCsv->openStream('r');

        while (($row = fgetcsv($dataCsv->stream, 0, ";", '"', '~')) !== false) {

            if (!isset($row[5])) dd($row);
            if ($row[5] !== '') {
                $normalEmails = [];
                foreach (explode("\n", $row[5]) as $email) {
                    if (preg_match(Email::$regexp, $email)) {
                        $normalEmails[] = $email;
                    }
                }
                $row[5] = implode("\n", $normalEmails);
            }

            $resultCsv->insertRow($row);
        }

        $resultCsv->closeStream();
        $dataCsv->closeStream();
        return 0;
    }
}
