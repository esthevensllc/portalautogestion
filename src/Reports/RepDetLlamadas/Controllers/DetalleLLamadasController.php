<?php

namespace AMovil\Reports\RepDetLlamadas\Controllers;

use AMovil\Reports\RepDetLlamadas\Domain\TipoReporte;
use AMovil\Reports\RepDetLlamadas\Services\ExportReporteLlamadas;
use Illuminate\Http\Request;

class DetalleLLamadasController
{
    private $service;

    public function __construct(ExportReporteLlamadas $service)
    {
        $this->service = $service;
    }

    public function entrantes(){
        $data = [
            'title' => 'Detalle llamadas / entrantes (Tráfico Cursado)',
            'url_export' => asset('rep-det-consumo/detalle-llamadas/entrantes/export'),
            'filename' => 'Detalle_llamadas_entrantes.xlsx',
        ];
        return view('rep_det_consumo.detalle_llamadas', compact('data'));
    }

    public function salientes(){
        $data = [
            'title' => 'Detalle llamadas / salientes (Tráfico Cursado)',
            'url_export' => asset('rep-det-consumo/detalle-llamadas/salientes/export'),
            'filename' => 'Detalle_llamadas_salientes.xlsx',
        ];
        return view('rep_det_consumo.detalle_llamadas', compact('data'));
    }

    public function entrantes_salientes(){
        $data = [
            'title' => 'Detalle llamadas / entrantes y entrantes (Tráfico Cursado)',
            'url_export' => asset('rep-det-consumo/detalle-llamadas/entrantes-salientes/export'),
            'filename' => 'Detalle_llamadas_entrantes_salientes.xlsx',
        ];
        return view('rep_det_consumo.detalle_llamadas', compact('data'));
    }

    public function exportEntrantes(Request $request){
        ini_set('max_execution_time', 4*60*60);
        set_time_limit(4*60*60);

        $export = $this->service->__invoke(
            TipoReporte::ENTRANTES,
            $request->input('periodo1'),
            $request->input('periodo2'),
            $request->input('tipo_input'),
            $request->input('lineas'),
            $request->hasFile('excel') ? $request->file('excel') : null,
            $request->input('numero_documento'),
            $request->input('numero_cuenta'),
            $request->input('cod_cliente'),
            $request->input('numeros_primarios')
        );
        return response($export, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="reporte.xlsx"'
        ]);
    }

    public function exportSalientes(Request $request){
        ini_set('max_execution_time', 4*60*60);
        set_time_limit(4*60*60);

        $export = $this->service->__invoke(
            TipoReporte::SALIENTES,
            $request->input('periodo1'),
            $request->input('periodo2'),
            $request->input('tipo_input'),
            $request->input('lineas'),
            $request->hasFile('excel') ? $request->file('excel') : null,
            $request->input('numero_documento'),
            $request->input('numero_cuenta'),
            $request->input('cod_cliente'),
            $request->input('numeros_primarios')
        );
        return response($export, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="reporte.xlsx"'
        ]);
    }

    public function exportEntrantesSalientes(Request $request){
        ini_set('max_execution_time', 4*60*60);
        set_time_limit(4*60*60);

        $export = $this->service->__invoke(
            TipoReporte::ENTRANTES_SALIENTES,
            $request->input('periodo1'),
            $request->input('periodo2'),
            $request->input('tipo_input'),
            $request->input('lineas'),
            $request->hasFile('excel') ? $request->file('excel') : null,
            $request->input('numero_documento'),
            $request->input('numero_cuenta'),
            $request->input('cod_cliente'),
            $request->input('numeros_primarios')
        );
        return response($export, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="reporte.xlsx"'
        ]);
    }
}
