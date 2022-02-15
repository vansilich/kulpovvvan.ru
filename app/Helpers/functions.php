<?php

use Carbon\Carbon;

if ( !function_exists('sortArrByTime')) {

    function sortArrByTime($a, $b): int
    {

        if ($a["day"] == $b["day"]) {
            return 0;
        }

        return (strtotime($a["day"]) < strtotime($b["day"])) ? -1 : 1;
    }
}

if ( !function_exists('splitByWeeks') ) {

    /**
     * Return array split by weeks
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
     * Check if email valid
     *
     * @param string $email
     * @return bool
     */
    function is_valid_email( string $email): bool
    {
        $pattern = "/^([a-z0-9_-]+\.)*[a-z0-9_-]*@([a-z0-9\-]+\.)+[a-z]{2,6}$/i";

        return preg_match($pattern, $email);
    }
}

if ( !function_exists('decodeGmailBody') ) {

    /**
     * Decode string from Base64
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

        return $decodedMessage;
    }
}
