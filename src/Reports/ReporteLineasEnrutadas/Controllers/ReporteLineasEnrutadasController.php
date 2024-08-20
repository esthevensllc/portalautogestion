<?php

namespace AMovil\Reports\ReporteLineasEnrutadas\Controllers;

use AMovil\Reports\ReporteLineasEnrutadas\Services\ExportReporteLineasEnrutadas;
use AMovil\Reports\OltCmts\Services\OltCmtsFinder;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;

class ReporteLineasEnrutadasController
{
    private $exporter;

    public function __construct(ExportReporteLineasEnrutadas $exporter)
    {
        $this->exporter = $exporter;
    }
    
    public function view()
    {
        $config = [
            'title' => 'Reporte Lineas Enrutadas',
            'url' => url('reporte-lineas-enrutadas/export'),
            "values" => [],
        ];
        return view("reporte_lineas_enrutadas.search_reporte_lineas_enrutadas", compact("config"));
    }

    public function export(Request $request)
    {
        $file = null;

        $file = new FileInput(
            $request->file("excel")->getPathname(),
            $request->file("excel")->getClientOriginalName()
        );
        
        $response = $this->exporter->__invoke(
            $file,
        )->data();

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $response["filename"] .'"'
        ]);
    }
}
