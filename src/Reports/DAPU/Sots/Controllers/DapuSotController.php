<?php

namespace AMovil\Reports\DAPU\Sots\Controllers;

use AMovil\Reports\DAPU\Sots\Services\DapuSotExporter;
use AMovil\Reports\DAPU\Sots\Services\DapuSotFinder;
use Illuminate\Http\Request;

class DapuSotController
{
    private DapuSotFinder $finder;
    private DapuSotExporter $export;

    public function __construct(DapuSotFinder $finder, DapuSotExporter $export)
    {
        $this->finder = $finder;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "CONSULTA SOT - DIR INSTALACION",
            "url" => asset("dapu/consulta-sot/json"),
            "url_export" => asset("dapu/consulta-sot/export"),
            "fields" => [
                "contd_fecha_contrato" => ["label" => "CONTD_FECHA_CONTRATO"],
                "dn_num" => ["label" => "DN_NUM"],
                "contn_numero_contrato" => ["label" => "CONTN_NUMERO_CONTRATO"],
                "contn_numero_sec" => ["label" => "CONTN_NUMERO_SEC"],
                "paquete" => ["label" => "PAQUETE"],
                "contc_oficina_venta" => ["label" => "CONTC_OFICINA_VENTA"],
                "pdv_canal" => ["label" => "PDV_CANAL"],
                "pdv_des" => ["label" => "PDV_DES"],
                "pdv_razon_social" => ["label" => "PDV_RAZON_SOCIAL"],
                "tipo_doc_cliente" => ["label" => "TIPO_DOC_CLIENTE"],
                "contv_nro_doc_cliente" => ["label" => "CONTV_NRO_DOC_CLIENTE"],
                "nombre_razon_social_cliente" => ["label" => "NOMBRE_RAZON_SOCIAL_CLIENTE"],
                "contv_codigo_vendedor" => ["label" => "CONTV_CODIGO_VENDEDOR"],
                "contv_vendedor" => ["label" => "CONTV_VENDEDOR"],
                "nro_sot" => ["label" => "NRO_SOT"],
                "fecultest" => ["label" => "FECULTEST"],
                "fecha_activacion" => ["label" => "FECHA_ACTIVACION"],
                "direccion" => ["label" => "DIRECCION"],
            ],
            "form_view" => "dapu.consulta_sot_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = $this->finder->__invoke(
            $request->input('doc_cliente'),
        )->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke(
            $request->input('doc_cliente'),
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
