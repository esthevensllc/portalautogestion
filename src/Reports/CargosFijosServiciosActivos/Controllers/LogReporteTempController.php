<?php

namespace AMovil\Reports\CargosFijosServiciosActivos\Controllers;

use AMovil\Reports\CargosFijosServiciosActivos\Services\DeleteLogReporteTemp;
use AMovil\Reports\CargosFijosServiciosActivos\Services\GetLogReporteTemp;

class LogReporteTempController
{
    private $searcher;
    private $deleter;

    public function __construct(GetLogReporteTemp $searcher, DeleteLogReporteTemp $deleter)
    {
        $this->searcher = $searcher;
        $this->deleter = $deleter;
    }

    public function view(){
        $config = [
            'title' => 'Descarga de reportes',
            'getApi' => url('cargos-fijos-servicios-activos/reportes/search'),
            'downloadApi' => url('cargos-fijos-servicios-activos/reportes/[file]/download'),
        ];
        return view('cargos_fijos_servicios_activos.reporte_log', compact('config'));
    }

    public function search()
    {
        $data = $this->searcher->__invoke();
        return response()->json(["data" => $data]);
    }

    public function download($file){
        $rutaArchivo = storage_path('app/cargos-fijos-servicios-activos/reportes/'.$file);
        $nombreArchivo = $file;
        return response()->download($rutaArchivo, $nombreArchivo);
        /*return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $response["filename"] .'"'
        ]);*/
    }

    public function delete()
    {
        $this->deleter->__invoke();
        return response()->json([]);
    }
}
