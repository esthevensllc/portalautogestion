<?php

namespace AMovil\Reports\DAPU\TramiteConsulta\Controllers;

use AMovil\Reports\DAPU\TramiteConsulta\Services\ExportReporteTramiteConsulta;
use Illuminate\Http\Request;

class TramiteConsultaController
{
    private $service;

    public function __construct(ExportReporteTramiteConsulta $service)
    {
        $this->service = $service;
    }

    public function view(){
        $reportes = [
            ['id' => '1', 'label' => 'FORMATO1'],
            ['id' => '2', 'label' => 'FORMATO2']
        ];
        $config = [
            "url" => asset("dapu/tramites-consulta/export")
        ];
        return view('dapu.tramite_consulta', compact('reportes', 'config'));
    }

    public function export(Request $request)
    {
        $csv = $request->file('file');
        $file = [
            'pathname' => $csv->getPathname(),
            'filename' => $csv->getClientOriginalName()
        ];

        $response = $this->service->__invoke($request->input('tipo_reporte'), $file)->data();
        return response($response['content'], 200, [
            'Content-Encoding' => 'UTF-8',
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
        ]);
    }
}
