<?php

namespace App\Console\Commands;

use App\Helpers\Api\BigQuery;
use App\Helpers\Csv;
use Illuminate\Console\Command;

class BigqueryHandler extends Command
{
    private array $companies = [
        63609246 => 'FluidErnst(Поиск)',
        63609248 => 'FluidErnst (Ретаргетинг)',
        63609251 => 'Уровнемеры (Поиск)',
        63609252 => 'Уровнемеры (РСЯ)',
        63609253 => 'Преобразователи уровня (Поиск)',
        63609255 => 'Преобразователи уровня (РСЯ)',
        63609256 => 'Расход (Поиск)',
        63609257 => 'Расход (РСЯ)',
        63609259 => 'Температура (Поиск)',
        63609261 => 'Температура (РСЯ)',
        63609262 => 'Давление(Поиск)',
        63609263 => 'Давление(РСЯ)',
        63609265 => 'Hy lok (Поиск)',
        63609266 => 'Конкуренты (Поиск)',
        63609269 => 'Трубопроводная арматура (Баннер на поиске)',
        63609272 => 'FluidErnst Казахстан (Поиск)',
        63609273 => 'FluidErnst СНГ (Поиск)',
        63609275 => 'Баннер на поиске (ПОИСК)',
        63609277 => 'Клиенты по ГЕО (РСЯ)',
        63609278 => 'Фитинги (Поиск)',
        63609279 => 'Регуляторы-Давления (Поиск)',
        63609280 => 'Манометры (Поиск)',
        63609285 => 'Ротаметры (Поиск)',
        63609286 => 'Реклама по счетам (РСЯ)',
        63609289 => '(Мероприятия) СПБ - 11 баннеров (РСЯ)',
        63609290 => '(Мероприятия) Нефтегаз 2022 - MAC/ГЕО',
        65077744 => 'Краны (Поиск)',
        68169426 => 'Конкуренты (РСЯ)',
        68526607 => 'Преобразователи давления, датчики (Поиск)',
        69793902 => 'Регуляторы давления мастер (Письмо)',
        70513502 => 'Манометры Мастер (Письмо)',
        70517716 => 'Манометры мастер (Звонок)',
        70611613 => 'Фитинги (мастер, контекст) звонок - 500 руб.',
        70614980 => 'Клапаны (Мастер, контекст) Звонок - 500 руб.',
        70651517 => 'Медийная кампания №38 от 27-01-2022',
        70976755 => 'Фитинги (мастер, контекст) Звонок 500 руб.',
        70978220 => 'Фитинги (мастер, контекст) Письмо 500 руб.',
        71345758 => 'Уровнемеры (мастер, контекст) письмо 300 руб.',
        71354930 => 'Уровнемеры (мастер, контекст) звонок 500 руб.',
        71396504 => 'Расходомеры мастер (Письмо 300) авто настройки',
        71464677 => 'Расходомеры мастер (Звонок 500)',
        71545051 => 'Температура мастер, контекст (Письмо 300)',
        71702595 => 'Преобразователи давления (мастер, контекст) Письмо 300',
        71924361 => 'Конкуренты Мастер. Остаемся. 09.03.22 - Контекст| Авто (Письмо - 700руб.)',
        71924441 => 'Конкуренты Мастер. Остаемся. 09.03.22 - Контекст| Авто (Письмо - 500руб.)',
        72167282 => 'Конкуренты / аудитории (РСЯ)',
        72289415 => 'Зажимы (мастер, контекст) Письмо 300',
        72309618 => 'Клапаны (Мастер, контекст) Письмо 300',
        72345024 => 'Температура мастер, контекст (Звонок 500)',
        72359233 => 'Преобразователи давления (мастер, контекст) Звонок 500',
        72414307 => 'Трубы (мастер, контекст) Письмо 300',
        72433383 => 'Трубы (мастер, контекст) Звонок 500',
        69907082 => 'Регуляторы давления (мастер) Звонок - 500 руб.',
    ];

    protected $signature = 'bigquery:handle';

    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $csv = new Csv( base_path('data/new_result.csv') );
        $csv->openStream();

        $regexp = $this->createRegexp();
        $offset = 3000;

        //headers
        $csv->insertRow([
            'company_name',
            'company_id',
            'REMOTE_ADDR',
            'HTTP_USER_AGENT',
            'SCREEN',
            'GEO_LOCATION',
            'ROISTAT_ID',
            'INVOICE_ID',
            'TIMESTAMP',
            'CLIENT_NAME',
            'URL',
            'HTTP_REFERER',
            'AUTH',
            'GA_MAIL',
            'GA_4',
            'YM_UID',
            'FINGERPRINT_ID',
        ]);

        $bigQuery = new BigQuery();
        for ($i = 0; $i < 1005000; $i += $offset) {

            $result = $bigQuery->getQueryResults("
            SELECT *
            FROM `peak-age-279206`.`googleDataStuidio`.`FL-visitors`
            LIMIT $offset
            OFFSET $i");

            foreach ($result as $row) {
                $company_data = [null, null];

                if ( preg_match($regexp, $row['URL'], $matches) ){
                    $id = trim($matches[0], 'cm_id=_');
                    $company_data = [$this->companies[$id], $id];
                }

                $csv->insertRow([
                    ...$company_data,
                    $row['REMOTE_ADDR'],
                    $row['HTTP_USER_AGENT'],
                    $row['SCREEN'],
                    $row['GEO_LOCATION'],
                    $row['ROISTAT_ID'],
                    $row['INVOICE_ID'],
                    $row['TIMESTAMP'],
                    $row['CLIENT_NAME'],
                    $row['URL'],
                    $row['HTTP_REFERER'],
                    $row['AUTH'],
                    $row['GA_MAIL'],
                    $row['GA_4'],
                    $row['YM_UID'],
                    $row['FINGERPRINT_ID'],
                ]);
            }
        }

        $csv->closeStream();
    }

    private function createRegexp(): string
    {
        $regexp = '#';

        foreach ($this->companies as $id => $name) {
            $regexp .= 'cm_id=('.$id.')\_|';
        }
        $regexp = trim($regexp, '|');
        $regexp .= '#';

        return $regexp;
    }
}
