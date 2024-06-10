<?php

namespace AMovil\Reports\DAPU\ListaExcepcionesImeiImsi\Controllers;

use AMovil\Reports\DAPU\ListaExcepcionesImeiImsi\Services\ExportListaExcepcionesImeiImsi;
use AMovil\Reports\DAPU\ListaExcepcionesImeiImsi\Services\ListaExcepcionesImeiImsiFinder;
use Illuminate\Http\Request;

class ListaExcepcionesImeiImsiController
{
    private $get;
    private $export;

    public function __construct(ListaExcepcionesImeiImsiFinder $get, ExportListaExcepcionesImeiImsi $export)
    {
        $this->get = $get;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "LISTA DE EXCEPCIONES - IMEI E IMSI",
            "url" => asset("dapu/lista-excepciones-imei-imsi/json"),
            "url_export" => asset("dapu/lista-excepciones-imei-imsi/export"),
            "fields" => [
                "transact_date" => ["label" => "FECHA"],
                "task_id" => ["label" => "TASK_ID"],
                "hlrsn" => ["label" => "HLRSN"],
                "operator" => ["label" => "OPERATOR"],
                "date_time" => ["label" => "DATE_TIME"],
                "command" => ["label" => "COMMAND"],
                "cod_cmd" => ["label" => "COD_CMD"],
                "cmd_result" => ["label" => "CMD_RESULT"],
                "imei" => ["label" => "IMEI"]
            ],
            "form_view" => "dapu.lista_excepciones_imei_imsi_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = $this->get->__invoke($request->input('imei'))->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke(
            $request->input('type'),
            $request->input('imei'),
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
