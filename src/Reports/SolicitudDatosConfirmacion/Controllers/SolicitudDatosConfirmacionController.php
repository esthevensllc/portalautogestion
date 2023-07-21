<?php

namespace AMovil\Reports\SolicitudDatosConfirmacion\Controllers;

use AMovil\Reports\SolicitudDatosConfirmacion\Services\SolicitudDatosConfirmacionService;
use Illuminate\Http\Request;

class SolicitudDatosConfirmacionController
{
    private $service;
    public function __construct(SolicitudDatosConfirmacionService $service)
    {
        $this->service = $service;
    }

    public function view()
    {
        $config = [
            "title" => "SOLICITUD DE DATOS/CONFIRMACIÓN",
            "url" => asset("solicitud-datos-confirmacion/export")
        ];
        return view("solicitud_datos_confirmacion.solicitud_datos_confirmacion", compact("config"));
    }

    public function getTable(Request $request)
    {
        
        $response = $this->service->getTable(
            $request->input("filter1"),
            $request->input("filter2"),
            $request->input("filter3"),
            $request->input("filter4")
        );

        return $response;
    }

    public function export(Request $request)
    {
        ini_set('max_execution_time', '7200');
        set_time_limit(7200);
        
        $response = $this->service->export(
            $request->input("filter1"),
            $request->input("filter2"),
            $request->input("filter3"),
            $request->input("filter4")
        )->data();

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
    }
}
