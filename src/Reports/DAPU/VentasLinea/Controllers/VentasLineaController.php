<?php

namespace AMovil\Reports\DAPU\VentasLinea\Controllers;

use AMovil\Reports\DAPU\VentasLinea\Services\ExportVentasLinea;
use AMovil\Reports\DAPU\VentasLinea\Services\GetVentasLinea;
use Illuminate\Http\Request;

class VentasLineaController
{
    private $get;
    private $export;

    public function __construct(GetVentasLinea $get, ExportVentasLinea $export)
    {
        $this->get = $get;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "VENTAS DE LINEA POSTPAGO",
            "url" => asset("dapu/ventas-linea/json"),
            "url_export" => asset("dapu/ventas-linea/export"),
            "fields" => [
                "fecha_venta" => ["label" => "FECHA_VENTA"],
                "num_sec" => ["label" => "NUM_SEC"],
                "linea" => ["label" => "LINEA"],
                "tipo_doc_cliente" => ["label" => "TIPO_DOC_CLIENTE"],
                "documento_cliente" => ["label" => "DOCUMENTO_CLIENTE"],
                "nombres_cliente" => ["label" => "NOMBRES_CLIENTE"],
                "apellidos_cliente" => ["label" => "APELLIDOS_CLIENTE"],
                "imei" => ["label" => "IMEI"],
                "codigo_vendedor" => ["label" => "CODIGO_VENDEDOR"],
                "nombres_vendedor" => ["label" => "NOMBRES_VENDEDOR"],
                "apellido_paterno_vendedor" => ["label" => "APELLIDO_PATERNO_VENDEDOR"],
                "apellido_materno_vendedor" => ["label" => "APELLIDO_MATERNO_VENDEDOR"],
                "documento_vendedor" => ["label" => "DOCUMENTO_VENDEDOR"],
                "canal_venta" => ["label" => "CANAL_VENTA"],
                "codigo_oficina_venta" => ["label" => "CODIGO_OFICINA_VENTA"],
                "desc_oficina_venta" => ["label" => "DESC_OFICINA_VENTA"],
            ],
            "form_view" => "dapu.ventas_linea_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = $this->get->__invoke(
            $request->input('dni'),
            $request->input('fono'),
        )->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke(
            $request->input('type'),
            $request->input('dni'),
            $request->input('fono'),
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
