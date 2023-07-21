<?php

namespace AMovil\Reports\BasesClientesPrepago\Controllers;

use AMovil\Reports\BasesClientesPrepago\Services\BasesClientesPrepagoService;
use Illuminate\Http\Request;

class BasesClientesPrepagoController
{
    private $service;
    public function __construct(BasesClientesPrepagoService $service)
    {
        $this->service = $service;
    }

    public function view()
    {
        $config = [
            "title" => "BASES CLIENTES PREPAGO",
            "url" => asset("bases-clientes-prepago/export")
        ];
        $departamentos = $this->service->getDep();
        return view("bases_clientes_prepago.bases_clientes_prepago", compact("config","departamentos"));
    }

    public function getUbigeo(Request $request)
    {
        if($request->input("type") == "selectDep"){
            $dep = $request->input("param1");   
            $data = $this->service->getProv($dep);
        }

        if($request->input("type") == "selectProv"){
            $dep = $request->input("param1");
            $prov = $request->input("param2");    
            $data = $this->service->getDist($dep,$prov);
        }
        
        return $data;
    }

    public function export(Request $request)
    {
        ini_set('max_execution_time', '7200');
        set_time_limit(7200);
        
        $response = $this->service->__invoke(
            $request->input("selectGranu"),
            $request->input("selectDep"),
            $request->input("selectProv"),
            $request->input("selectDist")
        )->data();

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
    }
}
