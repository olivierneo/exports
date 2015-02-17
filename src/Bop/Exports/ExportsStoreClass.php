<?php
/**
 * Created by PhpStorm.
 * User: olivier
 * Date: 17/02/2015
 * Time: 11:29
 */

namespace Bop\Exports;

use File;
use Log;


class ExportsStoreClass {

    use ExportsCsvTrait;

    public static function store($exportPath, $fileName, $datasToStore){

        if (!File::isWritable(storage_path($exportPath))) {
            try {
                $directory = File::makeDirectory(storage_path($exportPath), 0775, true);
            } catch (Exception $e) {
                Log::error('export.chunk.directory', $e->getMessage());
            }
        }

        foreach ($datasToStore as $line)
        {
            try {
                File::append(storage_path($exportPath . '/' . $fileName), ExportsCsvTrait::arrayToCsv($line));
            } catch (Exception $e) {
                Log::error('export.chunk.appends', $e->getMessage());
            }
        }

        return true;

        //fprintf(storage_path('exports/yo.csv'), chr(0xEF).chr(0xBB).chr(0xBF));

    }
}