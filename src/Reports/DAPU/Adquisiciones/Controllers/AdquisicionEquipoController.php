<?php

namespace AMovil\Reports\DAPU\Adquisiciones\Controllers;

use AMovil\Reports\DAPU\Adquisiciones\Services\ExportAdquisiciones;
use AMovil\Reports\DAPU\Adquisiciones\Services\GetAdquisiciones;
use Illuminate\Http\Request;

class AdquisicionEquipoController
{
    private $service;
    private $exportAdquisiciones;
    public function __construct(GetAdquisiciones $service, ExportAdquisiciones $exportAdquisiciones)
    {
        $this->service = $service;
        $this->exportAdquisiciones = $exportAdquisiciones;
    }

    public function view(){
        $config = [
            "url" => asset("dapu/adquisicion/json")
        ];
        return view('dapu.adquisicion', compact('config'));
    }

    public function getData(Request $request){
        $data = $this->service->__invoke($request->input('imei'))->data();
        return response()->json($data);
    }

    public function export(Request $request){
        $response = $this->exportAdquisiciones->__invoke(
            $request->input('imei'),
            $request->input('type'),
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
