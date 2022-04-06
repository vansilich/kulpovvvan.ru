<?php

namespace App\Helpers;

use Generator;

class Tsv
{

    public array $stdSettings = [
        'header_row'    =>true,
        'trim_headers'  =>true, //trim whitespace around header row values
        'trim_values'   =>true, //trim whitespace around all non-header row values
        'lb'            =>"\n", //line break character
        'tab'           =>"\t", //tab character
    ];

    public function stringTsvToArrayIterator( string $data_string, $args = [] ): Generator
    {
        $settings = [];
        foreach ($this->stdSettings as $key => $default) {
            $settings[$key] = array_key_exists($key, $args) ? $args[$key] : $default;
        }

        $lines = explode($settings['lb'], $data_string);
        $row = 0;

        foreach ($lines as $line) {

            if (empty($line)){
                break;
            }

            $row++;
            $data[$row] = [];
            $values = explode($settings['tab'], $line);

            foreach ($values as $c => $value) {
                $data[$row][$c] = $value;
            }

            yield $data[$row];
        }
    }

}
