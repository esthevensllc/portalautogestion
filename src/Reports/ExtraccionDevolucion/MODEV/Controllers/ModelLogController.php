<?php

namespace AMovil\Reports\ExtraccionDevolucion\MODEV\Controllers;

use AMovil\Reports\ExtraccionDevolucion\MODEV\Services\ExportModevLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ModelLogController
{
    private $exporter;

    public function __construct(ExportModevLog $exporter)
    {
        $this->exporter = $exporter;
    }

    public function view()
    {
        $config = [
            "title" => "FORMATO MODEV + LOG PREPAGO (VALIDACION)",
            "url" => asset("extraccion-devolucion/modev-log/export"),
        ];
        return view("extraccion_devolucion.modev_log", compact("config"));
    }

    public function export(Request $request){
        $ticket = $request->input("ticket");
        $response = $this->exporter->__invoke($ticket)->data();
        return Response::stream($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
    }
}
