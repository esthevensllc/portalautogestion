<?php

namespace AMovil\Reports\CargosFijosServiciosActivos\Controllers;

use AMovil\Reports\CargosFijosServiciosActivos\Services\ExportReporte;
use Illuminate\Http\Request;

class ReporteController
{
    private $service;

    public function __construct(ExportReporte $service)
    {
        $this->service = $service;
    }

    public function reporte(){
        $data = [
            'title' => 'CARGOS FIJOS Y SERVICIOS ACTIVOS / DESCARGAR REPORTE',
            'url_export' => asset('cargos-fijos-servicios-activos/reporte/export'),
            'filename' => 'Base de Usuario - Reporte de Cargos Fijos.xlsx',
        ];
        return view('cargos_fijos_servicios_activos.reporte', compact('data'));
    }

    public function descargaReporte(Request $request){
        ini_set('max_execution_time', '3600');

        $export = $this->service->__invoke(
            $request->input('tipo_input'),
            $request->input('lineas'),
            $request->hasFile('excel') ? $request->file('excel') : null,
            $request->input('numero_documento'),
            $request->input('numero_cuenta'),
            $request->input('sn')
        );
        if(is_array($export)){
            return response()->json($export);
        }
        return response()->json(['estado' => $export]);
    }
}
