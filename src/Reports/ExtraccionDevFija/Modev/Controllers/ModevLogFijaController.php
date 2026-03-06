<?php

namespace AMovil\Reports\ExtraccionDevFija\Modev\Controllers;

use AMovil\Reports\ExtraccionDevFija\Modev\Services\ExportModevLogFija;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ModevLogFijaController
{
    private $exporter;

    public function __construct(ExportModevLogFija $exporter)
    {
        $this->exporter = $exporter;
    }

    public function view()
    {
        $config = [
            "title" => "FORMATO MODEV",
            "url" => asset("extraccion-dev-fija/modev-log/export"),
        ];
        return view("extraccion_dev_fija.modev_log", compact("config"));
    }

    public function export(Request $request){
        $ticket = $request->input("ticket");
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

    public function logPrepagoView()
    {
        $config = [
            "title" => "LOG PREPAGO",
            "url" => asset("extraccion-dev-fija/log-prepago/export"),
        ];
        return view("extraccion_dev_fija.log_prepago", compact("config"));
    }

    public function exportLogPrepago(Request $request){
        $ticket = $request->input("ticket");
        $response = $this->exporter->exportLogPrepago($ticket);

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
