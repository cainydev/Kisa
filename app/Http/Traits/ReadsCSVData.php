<?php

namespace App\Http\Traits;

trait ReadsCSVData
{
    /**
     * Returns the CSV File as Array of Assocs
     */
    protected static function getCSV($filename, $seperator, $header = true, $popLast = true)
    {
        if (($handle = fopen(base_path('database/seeders/data/'.$filename), 'r')) !== false) {
            while (($csv[] = fgetcsv($handle, 0, $seperator)) !== false) {
            }
            fclose($handle);
        }

        if ($popLast) {
            array_pop($csv);
        }
        if ($header) {
            $keys = array_shift($csv);
            foreach ($csv as $i => $row) {
                $csv[$i] = array_combine($keys, $row);
            }
        }

        return $csv;
    }
}
