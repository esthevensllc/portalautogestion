<?php

namespace AMovil\Reports\RepRecargas\Controllers;

use AMovil\Reports\RepRecargas\Services\ExportDetalleRecargas;
use Illuminate\Http\Request;

class ReporteRecargasController
{
    private $exportRecargas;
    public function __construct(ExportDetalleRecargas $exportRecargas)
    {
        $this->exportRecargas = $exportRecargas;
    }

    public function detalle(){
        $data = [
            "title" => "Detalle recargas",
            "url" => asset("rep-recargas/detalle/export"),
        ];
        return view("rep_recargas.general", compact("data"));
    }

    public function extras(){
        $data = [
            "title" => "Detalle recargas extras",
            "url" => asset("rep-recargas/extras/export"),
        ];
        return view("rep_recargas.general", compact("data"));
    }

    public function exportDetalle(Request $request)
    {
        return $this->export("01", $request);
    }

    public function exportExtras(Request $request)
    {
        return $this->export("02", $request);
    }

    private function export($tipo_reporte, Request $request)
    {
        $response = $this->exportRecargas->__invoke(
            $tipo_reporte,
            $request->input("lineas"),
            $request->input("fecha1"),
            $request->input("fecha2")
        )->data();

        $headers_type = [
            'csv' => [
                'Content-Encoding' => 'UTF-8',
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
            ],
            'xlsx' => [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
            ],
        ];

        $headers = $headers_type[$response['type']];
        
        return response($response['content'], 200, $headers);
    }
}
