<?php

namespace AMovil\Reports\DetallePlanes\ConsolidadoMinutos\Controllers;

use AMovil\Reports\DetallePlanes\ConsolidadoMinutos\Services\ExportConsolidadoMinutos;
use Illuminate\Http\Request;

class ConsolidadoMinutosController
{
    private $exporter;

    public function __construct(ExportConsolidadoMinutos $exporter)
    {
        $this->exporter = $exporter;
    }

    public function view()
    {
        $config = [
            "title" => "Consolidado de Minutos",
            "url" => url("detalle-planes/consolidado-minutos/export"),
        ];
        return view("detalle_planes.export_consolidado_minutos", compact("config"));
    }

    public function export(Request $request)
    {
        $response = $this->exporter->__invoke(
            $request->input("num_cuenta"),
            $request->input("periodo")
        )->data();
        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $response["filename"] .'"'
        ]);
    }
}
