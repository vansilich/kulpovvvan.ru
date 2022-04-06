<?php

namespace App\Helpers;

class Email
{

    public static string $regexp = '#(?:[a-z0-9_-]+\.)*[a-z0-9_-]+@(?:[a-z0-9\-]+\.)+[a-z]{2,6}#i';

    /**
     * Search emails in text
     *
     * @param string $text - string with text with emails
     * @return array|false - array of matches or false if no emails found
     */
    public static function searchByRegexp( string $text ): array|false
    {
        preg_match_all( static::$regexp, $text, $emails );

        if ( empty($emails) ) {
            return false;
        }
        return $emails;
    }

}
