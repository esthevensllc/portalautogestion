<?php

namespace AMovil\Reports\DAPU\ServicioMovil\Controllers;

use AMovil\Reports\DAPU\ServicioMovil\Services\ExportServicioMovil;
use AMovil\Reports\DAPU\ServicioMovil\Services\ServicioMovilFinder;
use Illuminate\Http\Request;

class ServicioMovilController
{
    private $get;
    private $export;

    public function __construct(ServicioMovilFinder $get, ExportServicioMovil $export)
    {
        $this->get = $get;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "INFORMACION DEL SERVICIO MOVIL",
            "url" => asset("dapu/servicio-movil/json"),
            "url_export" => asset("dapu/servicio-movil/export"),
            "fields" => [
                "create_date" => ["label" => "CREATE_DATE"],
                "reason_3" => ["label" => "REASON_3"],
                "title" => ["label" => "TITLE"],
                "last_name" => ["label" => "LAST_NAME"],
                "first_name" => ["label" => "FIRST_NAME"],
                "x_doc_type" => ["label" => "X_DOC_TYPE"],
                "x_doc_num" => ["label" => "X_DOC_NUM"],
                "phone" => ["label" => "PHONE"],
                "lugar" => ["label" => "LUGAR"],
                "x_subclase_code" => ["label" => "X_SUBCLASE_CODE"],
            ],
            "form_view" => "dapu.servicio_movil_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = $this->get->__invoke(
            $request->input('msisdn'),
            $request->input('fecha')
        )->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke(
            $request->input('type'),
            $request->input('msisdn'),
            $request->input('fecha'),
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
