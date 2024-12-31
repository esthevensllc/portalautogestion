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
            "title" => "FORMATO MODEV + LOG PREPAGO (VALIDACION)",
            "url" => asset("extraccion-dev-fija/modev-log/export"),
        ];
        return view("extraccion_dev_fija.modev_log", compact("config"));
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
