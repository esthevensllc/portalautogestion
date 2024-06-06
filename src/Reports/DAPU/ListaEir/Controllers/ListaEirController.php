<?php

namespace AMovil\Reports\DAPU\ListaEir\Controllers;

use AMovil\Reports\DAPU\ListaEir\Services\ExportListaEir;
use AMovil\Reports\DAPU\ListaEir\Services\ListaEirFinder;
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
                "imei" => ["label" => "IMEI"],
                "fecha_ingreso_blo" => ["label" => "FECHA_INGRESO_BLO"],
            ],
            "form_view" => "dapu.lista_eir_form",
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
