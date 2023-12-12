<?php

namespace AMovil\Reports\General\BloqueoImei\Controllers;

use AMovil\Reports\General\BloqueoImei\Services\ControlEirImeiService;
use Illuminate\Http\Request;

class ControlEirImeiController
{
    private $getReportLog;
    
    public function __construct(ControlEirImeiService $getReportLog)
    {
        $this->getReportLog = $getReportLog;
    }

    public function view()
    {
        $config = [
            'title' => 'Control EIR IMEI Bloqueados',
            'url' => url('control-eir-imei')
        ];
        return view("bloqueo_imei.control_eir_imei", compact("config"));
    }

    public function search(Request $request)
    {
        $imei = $request->input('imei');        
        $response = $this->getReportLog->__invoke([['imei', $imei]])->data();
        return response()->json([
            "data" => $response
        ]);
    }
}
