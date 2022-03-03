<?php

namespace App\Console\Commands;

use App\Helpers\Api\Gmail;
use App\Helpers\Csv;
use Exception;
use Illuminate\Console\Command;

class SaveSendersByTriggers extends Command
{

    private array $triggers = [];
    private array $matches = [];

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'gmail:save-by-subj {manager}';

    /**
     * The console command description.
     */
    protected $description = 'Ищет все имейлы у {manager}, в subject писем которых входит параметр {subject}';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $triggers = "#KLS0649217
#KLS0663900
#KLS0693205
#KLS0651348
#KLS0672590
#ART0647453
#KLS0709384
#KLS0752250
#KLS0709384
#KLS0795287
#KLS0651999
#KLS0676757
#KLS0818538
#IVG0665020
#КАМ0709221
#ORD0660696
#ORD0660697
#ORD0660706
#KLS0685813
#ORD0674487
#KLS0647088
#KLS0810274
#KLS0818304
#KDA0671345
#KLS0628346
#KLS0673134
#KLS0645667
#LNM0686332
#DDN0769265
#DDN0769265
#KLS0748937
#ORD0659329
#ORD0659329
#KLS0635407
#MSN0808712
#KLS0685205
#BHF0686335
#ORD0661342
#KLS0629473
#KLS0677281
#ORD0647871
#KLS0708090
#ORD0733654
#KLS0735135
#KLS0736997
#KLS0746899
#KLS0684779
#KLS0740450
#KLS0741690
#KLS0702102
#KLS0704699
#KLS0647406
#KLS0651241
#KLS0808545
#KLS0721668
#KLS0708689
#KDA0671685
#SAP0769833
#ORD0661473
#SAP0768979
#SAP0769017
#KDA0768596
#KLS0634992
#KLS0660231
#KLS0735204
#KLS0735331
#KLS0742853
#KLS0785344
#KLS0693350
#KLS0679136
#KLS0819001
#KLS0800421
#KLS0801041
#KLS0759371
#KLS0766900
#KLS0812617
#KLS0778374
#KLS0748948
#KLS0628314
#КАМ0738812
#КАМ0738757
#КАМ0738689
#KYR0649715
#КАМ0774019
#KLS0646639
#KLS0635957
#KLS0761559
#KLS0438322
#ORD0660790
#KLS0758313
#KLS0633499
#KLS0664614
#IVG0665020
#KDA0731195
#KLS0636151
#KLS0692761
#KLS0650042
#KLS0716026
#KLS0773078
#BHF0734862
#KLS0638789
#KLS0812623
#KLS0818806
#KDA0769041
#KLS0643171
#ORD0732442
#KLS0732967
#KLS0732980
#KLS0733133
#KLS0747237
#KLS0676914";
        $this->triggers = explode( "\n", $triggers );
    }

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(): void
    {
        $manager = $this->argument('manager');

        $gmail_api = new Gmail();
        $gmail_api->setClient( $manager );
        $gmail_api->setupService();

        $nextPageToken = null;

        $bar = $this->output->createProgressBar( count($this->triggers) );
        $bar->start();
        foreach ( $this->triggers as $trigger) {

            do {
                $messagesList = $gmail_api->queryMessages([
                    'q' => 'subject:' . $trigger,
                    'pageToken' => $nextPageToken
                ]);

                foreach ($gmail_api->messagesTextIterator( $messagesList ) as $email_data) {

                    list( 'from' => $from, 'to' => $to ) = $email_data;
                    $address = preg_match( '#kalashnikov@fluid-line\.ru#u', $from) ? $to : $from;

                    preg_match('#(?:[a-z0-9_-]+\.)*[a-z0-9_-]*@(?:[a-z0-9\-]+\.)+[a-z]{2,6}#', $address, $matches);

                    if (empty($matches)) continue;

                    if ( !isset( $this->matches[ $manager ][ $trigger ] ) || !in_array($matches[0], $this->matches[ $manager ][ $trigger ]) ) {
                        $this->matches[ $manager ][ $trigger ][] = $matches[0];
                    }
                }

                $nextPageToken = $messagesList->getNextPageToken();

            } while ( $nextPageToken );

            $bar->advance();
        }

        $name = $manager . '_' . now()->timestamp . '.csv';
        $csv = new Csv( storage_path('app/public').'/'.$name );
        $csv->openStream();

        foreach ( $this->matches[ $manager ] as $trigger => $emails ) {
            foreach ($emails as $email) {
                $csv->insertRow([ $email, $trigger ]);
            }
        }

        $csv->closeStream();
        $bar->finish();
    }
}
