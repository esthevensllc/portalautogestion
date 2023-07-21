<?php

namespace App\Imports;

use App\Models\ReportesSigrei\sigrei;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class ReporteSigreiImport implements ToCollection
{
    private $userIdentifier;

    public function __construct($userIdentifier)
    {
        $this->userIdentifier = $userIdentifier;
    }

    /**
     * @param array $row
     *
     * @return ReporteSigrei|null
     */
    /*public function model(array $row)
    {
        if(is_numeric($row[0]) && is_numeric($row[1])){
            return new sigrei([
                'contador'             => $row[0],
                'imei'                 => $row[1],
                'estado_del_reporte'   => $row[2],
                'fecha_reporte'        => $row[3]
            ]);
        }
    }*/

    public function collection(Collection $rows)
    {
        $data = [];
        foreach ($rows as $row) 
        {
            if(is_numeric($row[0]) && is_numeric($row[1])){
                $data[] = [
                    'contador'             => $row[0],
                    'imei'                 => $row[1],
                    'estado_del_reporte'   => $row[2],
                    'fecha_reporte'        => $row[3]
                ];
            }
        }
        DB::table("reporte_sigrei_tmp_{$this->userIdentifier}")->insert($data);
    }

    public function batchSize(): int
    {
        return 1000;
    }
}