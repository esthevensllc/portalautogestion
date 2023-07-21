<?php

namespace AMovil\Reports\General\LineasMTCOsiptel\Services;

use DateTime;

class BaseExportLineas
{
    protected $storage;
    
    public function paginate($count_data, $perPage){
        // $count_data = 2000;
        $pages = ceil($count_data / $perPage);
        $old_max = 1;
        $ranges = [];
        $page = 1;
        $rows = 1;
        while ($page <= $pages) {
            $rows = $page * $perPage;
            $rows = $rows >= $count_data ? $count_data : $rows;
            // $ranges[] = "{$old_max} - {$rows}";
            $ranges[(string) $page] = ['min' => $old_max, 'max' => $rows];
            $old_max = $rows;
            $page++;
        }
        $response = ['pages' => $pages, 'ranges' => $ranges];
        return $response;
    }

    public function deleteOldFiles($path, $files_pattern, $files_pattern_delete)
    {
        $files = $this->storage->files($path);
        foreach($files as $file){
            if(str_contains($file, $files_pattern) && !str_contains($file, $files_pattern_delete)){
                $this->storage->delete($file);
            }
        }
    }
}
