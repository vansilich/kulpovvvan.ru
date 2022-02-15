<?php

namespace App\Handlers;

use App\Helpers\CsvHandler;
use Carbon\Carbon;

class FilesEmailsDuplicates
{

    /**
     * Удаляет дубликаты в массиве типа
     * [
     *      (string) $manager => [
     *          (string) $mail => (int) $duplicates
     * ]
     *
     * @param $tmp_arr
     * @param $managers
     * @return array
     */
    public static function removeDuplicates( $tmp_arr, $managers ): array
    {
        foreach ($tmp_arr as $values) {
            foreach ($values as $pochta => $duplicates) {
                $founded_in_managers = [];

                foreach ($managers as $manager) {
                    if ( isset($tmp_arr[$manager][$pochta]) ) {
                        // $manager => $duplicates
                        $founded_in_managers[$manager] = $tmp_arr[$manager][$pochta];
                    }
                }

                $founded_in_managers = self::saveManager( $founded_in_managers );

                foreach ($founded_in_managers as $manager => $not_used) {
                    unset( $tmp_arr[$manager][$pochta] );
                }
            }
        }

        return $tmp_arr;
    }

    /**
     * Оставляет менеджера с большим количеством дуюликатов, чем у остальных. Или же того, кто был первым попавшимся.
     *
     * @param array $duplicates_in_managers
     * @return array - массив значений, который должны быть удалены
     */
    private static function saveManager( array $duplicates_in_managers ): array
    {
        $alive_manager = array_search(max($duplicates_in_managers), $duplicates_in_managers);
        unset($duplicates_in_managers[$alive_manager]);

        return $duplicates_in_managers;
    }

    public static function saveToFile( array $managers, array $tmp_arr ): string
    {
        $name = Carbon::now()->timestamp . '.csv';
        $resultCsv = new CsvHandler(storage_path('app/public').'/'.$name);

        $resultCsv->openStream();

        //Установливаем заголовки
        $resultCsv->insertRow( ['pochta', 'manager'] );
        foreach ($managers as $manager) {

            foreach ($tmp_arr[$manager] as $pochta => $not_used) {
                $resultCsv->insertRow( [$pochta, $manager] );
            }
        }
        $resultCsv->closeStream();
        $resultCsv->deleteAfterResp();

        return $resultCsv->filePath;
    }

}
