<?php

namespace App\Imports;

use App\Models\ReportesSigrei\sigrei;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class ReporteSigreiImport implements ToModel, WithBatchInserts
{
    /**
     * @param array $row
     *
     * @return ReporteSigrei|null
     */
    public function model(array $row)
    {
        if(is_numeric($row[0]) && is_numeric($row[1])){
            return new sigrei([
                'contador'             => $row[0],
                'imei'                 => $row[1],
                'estado_del_reporte'   => $row[2],
                'fecha_reporte'        => $row[3]
            ]);
        }
    }

    public function batchSize(): int
    {
        return 1000;
    }
}