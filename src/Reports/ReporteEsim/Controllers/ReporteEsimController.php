<?php

namespace AMovil\Reports\ReporteEsim\Controllers;

use AMovil\Reports\ReporteEsim\Services\ExportReporteEsim;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;

class ReporteEsimController
{
    private $exporter;

    public function __construct(ExportReporteEsim $exporter)
    {
        $this->exporter = $exporter;
    }

    public function view()
    {
        $config = [
            'title' => 'Reporte ESIM',
            'url_export' => url('reporte-esim/export'),
        ];
        return view('reporte_esim.export_reporte_esim', compact('config'));
    }

    public function export(Request $request)
    {
        $nintex = $request->input("nintex");
        $file = new FileInput(
            $request->file("archivo")->getPathname(),
            $request->file("archivo")->getClientOriginalName()
        );
        $response = $this->exporter->__invoke($nintex, $file)->data();
        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $response["filename"] .'"'
        ]);
    }
}
