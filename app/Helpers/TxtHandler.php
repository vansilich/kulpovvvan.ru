<?php

namespace App\Helpers;

class TxtHandler extends File
{

    public static array $exceptedDomains = [
        '@fl15', '@mvif', '@nta-prom',
        '@swagelok',' @swagelok', '@gassys',
        '@wika',' @wika', '@stauff',
        '@stauff', '@hylok', '@dklok','@parkerservice', '@parker',
        '@fluid-line', '@fluidline', '@hy-lok'
    ];

    public static function arrMailsFromTxt( $file ): array
    {
        $tmp_array = [];

        $file_as_arr = self::fileRows( $file );
        $regexp = '#(' . implode(')|(', self::$exceptedDomains) . ')#';

        foreach ( $file_as_arr as $string) {
            $string_data = explode("	", $string);
            $pochta = $string_data[2] ?? null;

            if ( $pochta != null && !preg_match($regexp, $pochta)) {
                $pochta = mb_strtolower($pochta, 'UTF-8');

                $duplicates = isset( $tmp_array[$pochta] ) ? ++$tmp_array[$pochta] : 0;
                $tmp_array[ $pochta ] = $duplicates;
            }
        }

        return $tmp_array;
    }

    public static function fileRows( $file_path ): array
    {
        return explode("\n", file_get_contents($file_path));
    }

}
