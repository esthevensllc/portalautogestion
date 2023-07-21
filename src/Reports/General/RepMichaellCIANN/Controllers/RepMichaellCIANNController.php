<?php

namespace AMovil\Reports\General\RepMichaellCIANN\Controllers;

use AMovil\Reports\General\RepMichaellCIANN\Services\ExportRepMichaellCIANN;
use AMovil\Reports\General\RepMichaellCIANN\Services\GetInforFoNumCuenta;
use Illuminate\Http\Request;

class RepMichaellCIANNController
{
    private $export;
    private $serviceInfo;
    public function __construct(ExportRepMichaellCIANN $export, GetInforFoNumCuenta $serviceInfo)
    {
        $this->export = $export;
        $this->serviceInfo = $serviceInfo;
    }

    public function view()
    {
        $config = [
            "title" => "Reporte MICHAELL Y CIA NN",
            "url" => asset("michaell-cia-nn/export"),
            "info_url" => asset("michaell-cia-nn/num-cuenta-info"),
        ];
        return view("general.rep_michaell_cia_nn", compact("config"));
    }

    public function export(Request $request)
    {
        ini_set('max_execution_time', '1800');

        $response = $this->export->__invoke(
            $request->input("num_cuenta"),
            $request->input("periodo")
        )->data();

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
    }

    public function getInfoForNumCuenta($numCuenta)
    {
        $resp = $this->serviceInfo->__invoke($numCuenta)->data();
        return response()->json($resp);
    }
}
