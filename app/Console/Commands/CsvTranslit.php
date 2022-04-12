<?php

namespace App\Console\Commands;

use App\Helpers\Csv;
use Illuminate\Console\Command;

class CsvTranslit extends Command
{

    private array $compare = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'i', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'shch', 'ъ' => 'ie', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'iu', 'я' => 'ia', '-' => '-',
    ];

    protected $signature = 'csv:translit';

    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $data_file = new Csv(base_path('data/by.csv'));
        $data_file->openStream('r');

        $result_file = new Csv(base_path('data/by_translit.csv'));
        $result_file->openStream();

        while (($row = fgetcsv($data_file->stream, 0, ";")) !== false) {
//            if (preg_match('#\s[a-z]+\s#i', $row[0], $match)) {
//                $email = mb_strtolower(trim($match[0]));
//                $result_file->insertRow([$email]);
//            }
//
            $translit_surname = '';
            foreach (mb_str_split(mb_convert_encoding($row[0], 'UTF-8', 'Windows-1251')) as $letter) {
                $letter = mb_strtolower($letter);
                if (isset($this->compare[$letter])){
                    $translit_surname .= $this->compare[$letter];
                }
            }

            $result_file->insertRow([$translit_surname]);
        }

        $result_file->closeStream();
        $data_file->closeStream();
    }
}
