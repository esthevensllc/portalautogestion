<?php

namespace AMovil\Reports\Visanet\Controllers;

use AMovil\Reports\Visanet\Services\ExportVisanet;
use Illuminate\Http\Request;

class VisanetController
{
    private $service;

    public function __construct(ExportVisanet $service)
    {
        $this->service = $service;
    }

    public function view()
    {
        $config  = [
            "title" => "VISANET",
            "url" => asset("visanet/visanet/export"),
            "tipo_reporte" => [
                ["id" => 1, "label" => "Factuta_detallada_Visanet"],
                ["id" => 2, "label" => "Consumo_datos_visanet"],
            ]
        ];
        return view("visanet.visanet", compact("config"));
    }

    public function export(Request $request)
    {
        ini_set('max_execution_time', '7200');
        set_time_limit(7200);

        $response = $this->service->__invoke(
            $request->input("num_cuenta"),
            $request->input("periodo"),
            $request->input("tipo_reporte")
        )->data();
        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
    }
}
