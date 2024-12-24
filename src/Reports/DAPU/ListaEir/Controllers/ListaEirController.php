<?php

namespace AMovil\Reports\DAPU\ListaEir\Controllers;

use AMovil\Reports\DAPU\ListaEir\Services\ExportListaEir;
use AMovil\Reports\DAPU\ListaEir\Services\ListaEirFinder;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;

class ListaEirController
{
    private $get;
    private $export;

    public function __construct(ListaEirFinder $get, ExportListaEir $export)
    {
        $this->get = $get;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "LISTA EIR",
            "url" => asset("dapu/lista-eir/json"),
            "url_export" => asset("dapu/lista-eir/export"),
            "fields" => [
                "transact_date" => ["label" => "FECHA"],
                "task_id" => ["label" => "TASK_ID"],
                "hlrsn" => ["label" => "HLRSN"],
                "operator" => ["label" => "OPERATOR"],
                "date_time" => ["label" => "DATE_TIME"],
                "command" => ["label" => "COMMAND"],
                "cod_cmd" => ["label" => "COD_CMD"],
                "cmd_result" => ["label" => "CMD_RESULT"],
                "imei" => ["label" => "IMEI"],
            ],
            "form_method" => "POST",
            "form_view" => "dapu.lista_eir_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = null;
        if((int) $request->input("tipo_input") === 1){
            $values = explode(",", str_replace(" ", "", $request->input('imei')));
            $response = $this->get->__invoke($values);
        } else if((int) $request->input("tipo_input") === 2) {
            $file = new FileInput(
                $request->file("file")->getPathname(),
                $request->file("file")->getClientOriginalName()
            );
            $response = $this->get->fromFile($file);
        }
        if($response->fails()){
            return response()->json($response->errors(), 400);
        }
        $response = $response->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = null;
        if((int) $request->input("tipo_input") === 1){
            $values = explode(",", str_replace(" ", "", $request->input('imei')));
            $response = $this->export->__invoke($values, $request->input('type'));
        } else if((int) $request->input("tipo_input") === 2) {
            $file = new FileInput(
                $request->file("file")->getPathname(),
                $request->file("file")->getClientOriginalName()
            );
            $response = $this->export->fromFile($file, $request->input('type'));
        }

        if($response->fails()){
            return response()->json($response->errors(), 400);
        }

        $response = $response->data();

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
