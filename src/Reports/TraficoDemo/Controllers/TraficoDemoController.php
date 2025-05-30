<?php

namespace AMovil\Reports\TraficoDemo\Controllers;

use AMovil\Reports\TraficoDemo\Services\TraficoDemoExporter;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class TraficoDemoController
{
    private $exporter;

    public function __construct(TraficoDemoExporter $exporter)
    {
        $this->exporter = $exporter;
    }

    public function view()
    {
        $config = [
            "title" => "TRAFICO DEMO",
            "url" => url("trafico-demo/export"),
        ];
        return view("trafico_demo.trafico_demo", compact("config"));
    }

    public function export(Request $request){
        $file = null;
        if ($request->file("excel")) {
            $file = new FileInput(
                $request->file("excel")->getPathname(),
                $request->file("excel")->getClientOriginalName()
            );
        }
        $response = $this->exporter->__invoke(
            $request->input("fecha_ini"),
            $request->input("fecha_fin"),
            $file
        )->data();
        return response()->download($response['content'], $response['filename']);
    }
}
