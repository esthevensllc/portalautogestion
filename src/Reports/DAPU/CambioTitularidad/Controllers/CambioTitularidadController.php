<?php

namespace AMovil\Reports\DAPU\CambioTitularidad\Controllers;

use AMovil\Reports\DAPU\CambioTitularidad\Services\CambioTitularidadFinder;
use AMovil\Reports\DAPU\CambioTitularidad\Services\ExportCambioTitularidad;
use Illuminate\Http\Request;

class CambioTitularidadController
{
    private $finder;
    private $export;

    public function __construct(CambioTitularidadFinder $finder, ExportCambioTitularidad $export)
    {
        $this->finder = $finder;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "CAMBIO DE TITULARIDAD",
            "url" => asset("dapu/cambio-titularidad/json"),
            "url_export" => asset("dapu/cambio-titularidad/export"),
            "fields" => [
                "codigo_subclase" => ["label" => "CODIGO_SUBCLASE"],
                "tipo_linea" => ["label" => "TIPO_LINEA"],
                "desc_tipificacion" => ["label" => "DESC_TIPIFICACION"],
                "fecha_transaccion" => ["label" => "FECHA_TRANSACCION"],
                "numero_telefono" => ["label" => "NUMERO_TELEFONO"],
                "nombre_cliente_cedente" => ["label" => "NOMBRE_CLIENTE_CEDENTE"],
                "numero_doc_cedente" => ["label" => "NUMERO_DOC_CEDENTE"],
                "tipo_doc_cedente" => ["label" => "TIPO_DOC_CEDENTE"],
                "nombre_cliente_receptor" => ["label" => "NOMBRE_CLIENTE_RECEPTOR"],
                "tipo_doc_receptor" => ["label" => "TIPO_DOC_RECEPTOR"],
                "numero_doc_receptor" => ["label" => "NUMERO_DOC_RECEPTOR"],
                "codigo_tipificacion" => ["label" => "CODIGO_TIPIFICACION"],
                "usuario_reg" => ["label" => "USUARIO_REG"],
                "punto_atencion" => ["label" => "PUNTO_ATENCION"],
            ],
            "form_view" => "dapu.cambio_titularidad_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = $this->finder->__invoke(
            $request->input('linea'),
        )->data();
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
