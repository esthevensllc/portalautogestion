<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Controllers;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\CargarReporte;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ValidacionRecargaExporter;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ValidacionRecargaUpdater;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ValidacionRecargaController
{
    private $updater;
    private $exporter;

    public function __construct(ValidacionRecargaUpdater $updater, ValidacionRecargaExporter $exporter){
        $this->updater = $updater;
        $this->exporter = $exporter;
    }

    public function view()
    {
        $config = [
            "title" => "VALIDACIÓN RECARGAS PREPAGO",
            "updateApi" => asset("extraccion-devolucion/validacion-recargas-pre/update"),
            "exportApi" => asset("extraccion-devolucion/validacion-recargas-pre/export"),
        ];
        return view("extraccion_devolucion.validacion_recargas_pre", compact("config"));
    }

    public function update(Request $request)
    {
        $ticket = $request->input('ticket');
        $this->updater->__invoke($ticket);
        $response = $this->exporter->__invoke($ticket);
        if ($response->fails()) {
            return response()->json($response->errors(), 400);
        }
        $responseData = $response->data();
        return Response::stream($responseData["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$responseData["filename"].'"'
        ]);
    }

    public function export(Request $request)
    {
        $ticket = $request->input('ticket');
        $response = $this->exporter->__invoke($ticket);
        if ($response->fails()) {
            return response()->json($response->errors(), 400);
        }
        $responseData = $response->data();
        return Response::stream($responseData["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$responseData["filename"].'"'
        ]);
    }
}