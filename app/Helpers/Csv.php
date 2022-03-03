<?php

namespace App\Helpers;

class Csv extends File
{
    public static string $separator = ';';
    public static string $enclosure = '"';

    public static array $exceptedDomains = [
        '@fl15', '@mvif', '@nta-prom',
        '@swagelok',' @swagelok', '@gassys',
        '@wika',' @wika', '@stauff',
        '@stauff', '@hylok', '@dklok','@parkerservice', '@parker',
        '@fluid-line', '@fluidline', '@hy-lok'
    ];

    public static function arrFromMailsCsv( $stream ): array
    {
        $tmp_array = [];
        $regexp = '#(' . implode(')|(', self::$exceptedDomains) . ')#';

        while ( ( $row = fgetcsv($stream, 0, self::$separator, self::$enclosure) ) !== false)
        {
            $pochta = $row[0];

            if ($pochta != null && !preg_match($regexp, $pochta)) {
                $pochta = mb_strtolower($pochta, 'UTF-8');

                $duplicates = isset($tmp_array[$pochta]) ? ++$tmp_array[$pochta] : 0;
                $tmp_array[$pochta] = $duplicates;
            }
        }

        return $tmp_array;
    }

    public function insertRow( array $row )
    {
        fputcsv($this->stream, $row, self::$separator, self::$enclosure);
    }

}
