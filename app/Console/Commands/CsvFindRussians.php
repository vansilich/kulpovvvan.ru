<?php

namespace App\Console\Commands;

use App\Helpers\Csv;
use Illuminate\Console\Command;

class CsvFindRussians extends Command
{

    protected $signature = 'csv:findRussians';

    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $data_file = new Csv(base_path('data/emails_solidworks_forum.csv'));
        $data_file->openStream('r');

        $result_file = new Csv(base_path('data/emails_solidworks_rus4.csv'));
        $result_file->openStream();

        $surnames = $this->getSurnames();
        $names = $this->getNames();

        $count = 0;
        while (($row = fgetcsv($data_file->stream, 0, ";")) !== false){
            $count++;
            if ($count === 1) continue;

            $email = trim( mb_strtolower($row[0]) );

            if (preg_match('#^(?:[a-z0-9_-]+\.)*[a-z0-9_-]+@(?:[a-z0-9\-]+\.)+(ru|kz|bg|by|ua)$#i', $email)){
                $result_file->insertRow([$email]);
            }
            else {
                if (preg_match('#^(?:[a-z0-9_-]+\.)*[a-z0-9_-]+ov@(?:[a-z0-9\-]+\.)+[a-z]{2,6}$#i', $email)) {
                    $result_file->insertRow([$email]);
                    continue;
                }
                foreach ($names as $name) {
                    if (preg_match('#((?:[0-9\-\_\.@]|^)'.$name.'[0-9\-\_\.@])#i', $email)) {
                        $result_file->insertRow([$email]);
                        continue 2;
                    }
                }
                foreach ($surnames as $surname) {
                    if (preg_match('#((?:[0-9\-\_\.@]|^)'.$surname.'[0-9\-\_\.@])#i', $email)) {
                        dump($surname, $email);
                        $result_file->insertRow([$email]);
                        continue 2;
                    }
                }
            }
        }

        $data_file->closeStream();
        $result_file->closeStream();
    }

    private function getSurnames(): array
    {
        $surnamesCSV = new Csv(base_path('data/surnames_translit.csv'));
        $surnamesCSV->openStream('r');

        $surnames = [];
        while (($row = fgetcsv($surnamesCSV->stream, 0, ";")) !== false){
            if ( !empty($row[0]) ){
                $surnames[] = $row[0];
            }
        }

        return $surnames;
    }

    private function getNames(): array
    {
        $namesCSV = new Csv(base_path('data/names_translit.csv'));
        $namesCSV->openStream('r');

        $names = [];
        while (($row = fgetcsv($namesCSV->stream, 0, ";")) !== false){
            if ( !empty($row[0]) ){
                $names[] = $row[0];
            }
        }

        return $names;
    }
}
