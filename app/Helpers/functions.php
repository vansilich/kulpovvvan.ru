<?php

use Carbon\Carbon;

if ( !function_exists('sortArrByTime')) {

    /**
     * Сортирует массивы по неделям
     *
     * @param $a array
     * @param $b array
     * @return int
     */
    function sortArrByTime( array $a, array $b ): int
    {

        if ($a["day"] == $b["day"]) {
            return 0;
        }

        return (strtotime($a["day"]) < strtotime($b["day"])) ? -1 : 1;
    }
}

if ( !function_exists('splitByWeeks') ) {

    /**
     * Распределяет даты в масссиве по неделям
     *
     * @param array $data
     * @return array
     */
    function splitByWeeks( array $data ): array
    {
        if (empty($data)) return [];

        $week_start_day_ISO = (new Carbon( $data[0]['day'] ))->dayOfWeekIso;

        $first_week = [ array_splice( $data, 0, 8 - $week_start_day_ISO ) ];
        $other_weeks = array_chunk($data, 7);

        return array_merge($first_week, $other_weeks);
    }
}

if ( !function_exists('is_valid_email') ) {

    /**
     * Проверка валидности Email
     *
     * @param string $email
     * @return bool
     */
    function is_valid_email( string $email ): bool
    {
        $pattern = "/^([a-z0-9_-]+\.)*[a-z0-9_-]*@([a-z0-9\-]+\.)+[a-z]{2,6}$/i";

        return preg_match($pattern, $email);
    }
}

if ( !function_exists('decodeGmailBody') ) {

    /**
     * Декодирует строку, переданную как текст письма Gmail
     *
     * @param $rawData
     * @return bool|string
     */
    function decodeGmailBody( $rawData ): bool|string
    {
        $sanitizedData = strtr($rawData, '-_', '+/');
        $decodedMessage = base64_decode($sanitizedData);

        if( !$decodedMessage ){
            return false;
        }

        return urldecode($decodedMessage);
    }
}

if ( !function_exists('limitedFuncRetry') ) {

    /**
     * Функция для обработки API запросов, периодически отказывающих из-за throttling.
     *
     * @param int $depth - количество вызовов
     * @param int $delay - задержка между вызовами в секундах
     * @param callable $fn - функция, которая будет вызываться
     * @return mixed - результат вызова $fn()
     *
     * @throws Exception
     */
    function limitedFuncRetry( int $depth, int $delay, callable $fn ): mixed
    {
        for ($i = $depth; $i > 0; $i--) {

            try {
                return $fn();
            } catch ( Exception $exception ) {
                if ($i - 1 === 0) {
                    throw $exception;
                }
                sleep( $delay );
            }
        }

        throw new Exception('Функция не смогла вернуть результат без ошибки');
    }
}
