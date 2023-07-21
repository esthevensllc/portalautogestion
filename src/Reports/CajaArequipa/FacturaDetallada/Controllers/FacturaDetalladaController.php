<?php

namespace AMovil\Reports\CajaArequipa\FacturaDetallada\Controllers;

use AMovil\Reports\CajaArequipa\FacturaDetallada\Services\ExportFacturaDetallada;
use Illuminate\Http\Request;

class FacturaDetalladaController
{
    private $service;

    public function __construct(ExportFacturaDetallada $service)
    {
        $this->service = $service;
    }

    public function view()
    {
        $config = [
            "title" => "CUOTAS,SERVICIOS,OCC,ROAMING,PLAN",
            "url" => asset("caja-arequipa/factura-detallada/export")
        ];
        return view("caja_arequipa.factura_detallada", compact("config"));
    }

    public function export(Request $request)
    {
        $response = $this->service->__invoke(
            $request->input("num_cuenta"),
            $request->input("periodo")
        )->data();

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $response["filename"] .'"'
        ]);
    }
}
