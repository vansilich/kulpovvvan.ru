<?php

namespace App\Helpers;

class Email
{

    public static string $regexp = '#(?:[a-z0-9_-]+\.)*[a-z0-9_-]*@(?:[a-z0-9\-]+\.)+[a-z]{2,6}#';

    /**
     * Search emails in text
     *
     * @param string $data - string with text with emails
     * @return array|false - false if no emails found or array with emails
     */
    public static function searchByRegexp( string $data ): array|false
    {
        preg_match_all( static::$regexp, $data, $emails );

        if ( empty($emails) ) {
            return false;
        }
        return $emails;
    }

}
