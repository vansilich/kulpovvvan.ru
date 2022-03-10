<?php

namespace App\Helpers;

class Phone
{

    public static string $regexp = '#(?:\+?(8|7)[\- ]?)(\(?\d{3}\)?[\- ]?)[\d\- ]{7,10}#';

    public static function searchByRegexp( string $data ): false|array
    {
        preg_match_all( static::$regexp, $data, $phones );
        if ( empty($phones) ) {
            return false;
        }
        return $phones;
    }

}
