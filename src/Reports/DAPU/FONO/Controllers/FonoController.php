<?php

namespace AMovil\Reports\DAPU\FONO\Controllers;

use AMovil\Reports\DAPU\FONO\Services\ExportDataDni;
use AMovil\Reports\DAPU\FONO\Services\GetDataDni;
use Illuminate\Http\Request;

class FonoController
{
    private $getDataDni;
    private $export;

    public function __construct(GetDataDni $getDataDni, ExportDataDni $export)
    {
        $this->getDataDni = $getDataDni;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "BUSCAR DNI POR FONO",
            "url" => asset("dapu/consulta-fono/json"),
            "url_export" => asset("dapu/consulta-fono/export"),
            "fields" => [
                "fono" => ["label" => "FONO"],
                "nombre" => ["label" => "NOMBRE"],
                "fecha_alta" => ["label" => "FECHA_ALTA"],
                "fecha_baja" => ["label" => "FECHA_BAJA"],
                "numero_doc" => ["label" => "NUMERO_DOC"],
                "tipo_doc" => ["label" => "TIPO_DOC"],
            ],
            "form_view" => "dapu.consulta_dni_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = $this->getDataDni->__invoke($request->input("fono"))->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke(
            $request->input('fono'),
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
