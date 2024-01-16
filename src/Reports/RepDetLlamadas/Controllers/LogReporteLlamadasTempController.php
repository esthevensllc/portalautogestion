<?php

namespace AMovil\Reports\RepDetLlamadas\Controllers;

use AMovil\Reports\RepDetLlamadas\Services\DeleteLogReporteLlamadasTemp;
use AMovil\Reports\RepDetLlamadas\Services\GetLogReporteLlamadasTemp;

class LogReporteLlamadasTempController
{
    private $searcher;
    private $deleter;

    public function __construct(GetLogReporteLlamadasTemp $searcher, DeleteLogReporteLlamadasTemp $deleter)
    {
        $this->searcher = $searcher;
        $this->deleter = $deleter;
    }

    public function view(){
        $data = [
            'title' => 'Descarga de reportes',
            'api' => url('rep-det-consumo/reportes-log/search'),
            'storagePath' => "/portalautogestion_rel_llamadas"
        ];
        return view('rep_det_consumo.llamadas_reporte_log', compact('data'));
    }

    public function search()
    {
        $data = $this->searcher->__invoke();
        return response()->json(["data" => $data]);
    }

    public function delete()
    {
        $this->deleter->__invoke();
        return response()->json([]);
    }
}
