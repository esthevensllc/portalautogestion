<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Controllers;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\CargarReporte;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\DiligenciasWebExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class DiligenciasWebController
{
    private $updater;
    private $exporter;

    public function __construct(DiligenciasWebExporter $exporter){
        $this->exporter = $exporter;
    }

    public function view()
    {
        $config = [
            "title" => "DILIGENCIAS WEB",
            "exportApi" => asset("extraccion-devolucion/diligencias-web/export"),
        ];
        return view("extraccion_devolucion.diligencias_web", compact("config"));
    }

    public function export(Request $request)
    {
        $ticket = $request->input('ticket');
        $tipo_reporte = $request->input('tipo_reporte');
        $response = [];
        if ($tipo_reporte === '1') {
            $response = $this->exporter->byCorreo($ticket);
        } else if ($tipo_reporte === '2') {
            $response = $this->exporter->byDocumento($ticket);
        } else {
            $response = $this->exporter->byCorreoAndDocumento($ticket);
        }
        if ($response->fails()) {
            return response()->json($response->errors(), 400);
        }
        $responseData = $response->data();
        if ($responseData['type'] === 'xlsx') {
            return Response::stream($responseData["content"], 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="'.$responseData["filename"].'"'
            ]);
        } else {
            return response($responseData["content"], 200, [
                'Content-Type' => 'application/zip; charset=UTF-8',
                'Content-Transfer-Encoding' => 'Binary',
                'Content-Disposition' => 'attachment;filename="'.$responseData['filename'].'"'
            ]);
        }

    }
}