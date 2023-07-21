<?php

namespace AMovil\Reports\DAPU\SuspensionServicio\Controllers;

use AMovil\Reports\DAPU\SuspensionServicio\Services\ExportSuspensionServicio;
use AMovil\Reports\DAPU\SuspensionServicio\Services\GetReporteSuspensionServicio;
use Illuminate\Http\Request;

class SuspensionServicioController
{
    private $service;
    private $exportSuspensiones;

    public function __construct(GetReporteSuspensionServicio $service, ExportSuspensionServicio $exportSuspensiones)
    {
        $this->service = $service;
        $this->exportSuspensiones = $exportSuspensiones;
    }

    public function view(){
        $config = [
            "url" => asset("dapu/suspension-servicio/json")
        ];
        return view('dapu.suspension_servicio', compact('config'));
    }

    public function getData(Request $request){
        $data = $this->service->__invoke(
            $request->input('numero'),
            $request->input('dni'),
            $request->input('fecha1'),
            $request->input('fecha2')
        )->data();
        return response()->json($data);
    }

    public function export(Request $request){
        $response = $this->exportSuspensiones->__invoke(
            $request->input('numero'),
            $request->input('dni'),
            $request->input('fecha1'),
            $request->input('fecha2'),
            $request->input('type')
        )->data();

        $headers_type = [
            'csv' => [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
            ],
            'xlsx' => [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
            ],
        ];

        $headers = $headers_type[$response['type']];
        if(array_key_exists('message', $response)){
            $headers['Custom-message'] = $response['message'];
        }
        
        return response($response['content'], 200, $headers);
    }
}
