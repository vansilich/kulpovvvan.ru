<?php

namespace App\Console\Commands;

use App\Helpers\Csv;
use Illuminate\Console\Command;

class CsvToArr extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'to:arr';

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
        $triggers = new Csv( base_path('data/triggers.csv') );
        $triggers->openStream('r');

        $newArr = [];
        while ( ($trigger = fgetcsv($triggers->stream)) !== false ) {

            if ( preg_match('#\#[a-z]+[\d\\\]+#mi', $trigger[0]) &&
                !in_array($trigger[0], $newArr)
            ) {
                $newArr[] = $trigger[0];
            }
        }

        $triggers->closeStream();

        $output = new Csv( base_path('data/output.csv') );
        $output->openStream();

        foreach ($newArr as $value) {
            $output->insertRow([$value]);
        }

        $output->closeStream();

        return 0;
    }
}
