<?php

namespace AMovil\Reports\DAPU\Lineas\Controllers;

use AMovil\Reports\DAPU\Lineas\Services\ExportDataLinea;
use AMovil\Reports\DAPU\Lineas\Services\GetDataLinea;
use Illuminate\Http\Request;

class LineaController
{
    private $getDataLinea;
    private $export;

    public function __construct(GetDataLinea $getDataLinea, ExportDataLinea $export)
    {
        $this->getDataLinea = $getDataLinea;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "CONSULTA LINEA",
            "url" => asset("dapu/consulta-linea/json"),
            "url_export" => asset("dapu/consulta-linea/export"),
        ];
        return view("dapu.consultar_linea", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = $this->getDataLinea->__invoke($request->input("linea"))->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke(
            $request->input('linea'),
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
        
        return response($response['content'], 200, $headers);
    }
}
