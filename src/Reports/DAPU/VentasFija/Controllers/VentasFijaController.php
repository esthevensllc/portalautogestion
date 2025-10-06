<?php

namespace AMovil\Reports\DAPU\VentasFija\Controllers;

use AMovil\Reports\DAPU\VentasFija\Services\VentasFijaExporter;
use AMovil\Reports\DAPU\VentasFija\Services\VentasFijaFinder;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;

class VentasFijaController
{
    private $finder;
    private $export;

    public function __construct(VentasFijaFinder $finder, VentasFijaExporter $export)
    {
        $this->finder = $finder;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "VENTAS FIJA",
            "url" => asset("dapu/ventas-fija/json"),
            "url_export" => asset("dapu/ventas-fija/export"),
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
                "contrata" => ["label" => "CONTRATA"],
                "direccion" => ["label" => "DIRECCION"],
            ],
            "form_method" => "POST",
            "form_view" => "dapu.ventas_fija_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $input = $this->getInputFromRequest($request);
        $response = null;
        if($input["array"] !== null){
            $response = $this->finder->__invoke($request->input('tipo_input'), $input["array"])->data();
        } else {
            $response = $this->finder->fromFile($request->input('tipo_input'), $input["file"])->data();
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
            $values = explode(",", str_replace(" ", "", $request->input('num_documento')));
        } else if ($tipoInput === 2){
            $file = new FileInput(
                $request->file("file_num_documento")->getPathname(),
                $request->file("file_num_documento")->getClientOriginalName()
            );
        } else if($tipoInput === 3){
            $values = explode(",", str_replace(" ", "", $request->input('sot')));
        } else if ($tipoInput === 4){
            $file = new FileInput(
                $request->file("file_sot")->getPathname(),
                $request->file("file_sot")->getClientOriginalName()
            );
        }
        return ["array" => $values, "file" => $file];
    }
}
