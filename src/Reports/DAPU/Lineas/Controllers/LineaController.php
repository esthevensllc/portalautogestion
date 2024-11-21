<?php

namespace AMovil\Reports\DAPU\Lineas\Controllers;

use AMovil\Reports\DAPU\Lineas\Services\ExportDataLinea;
use AMovil\Reports\DAPU\Lineas\Services\GetDataLinea;
use AMovil\Shared\Application\FileInput;
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
        $input = $this->getInputFromRequest($request);
        $response = null;
        if($input["array"] !== null){
            $response = $this->getDataLinea->__invoke($request->input('tipo_input'), $input["array"])->data();
        } else {
            $response = $this->getDataLinea->fromFile($request->input('tipo_input'), $input["file"])->data();
        }
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $input = $this->getInputFromRequest($request);
        $response = null;
        if($input["array"] !== null){
            $response = $this->export->__invoke(
                $request->input('tipo_input'),
                $input["array"],
                $request->input('type'),
            )->data();
        } else {
            $response = $this->export->fromFile(
                $request->input('tipo_input'),
                $input["file"],
                $request->input('type'),
            )->data();
        }

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

    private function getInputFromRequest(Request $request){
        $tipoInput = (int) $request->input('tipo_input');
        $values = null;
        $file = null;
        if($tipoInput === 1){
            $values = explode(",", str_replace(" ", "", $request->input('linea')));
        } else if ($tipoInput === 2){
            $values = explode(",", str_replace(" ", "", $request->input('dni')));
        } else if ($tipoInput === 3){
            $file = new FileInput(
                $request->file("file_msisdn")->getPathname(),
                $request->file("file_msisdn")->getClientOriginalName()
            );
        } else if ($tipoInput === 4){
            $file = new FileInput(
                $request->file("file_dni")->getPathname(),
                $request->file("file_dni")->getClientOriginalName()
            );
        }
        return ["array" => $values, "file" => $file];
    }
}
