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
            "title" => "VENTAS DE LINEA",
            "url" => asset("dapu/ventas-linea/json"),
            "url_export" => asset("dapu/ventas-linea/export"),
            "fields" => [
                "fecha_venta" => ["label" => "FECHA_VENTA"],
                "imei" => ["label" => "IMEI"],
                "n_doc" => ["label" => "N_DOC"],
                "cliente" => ["label" => "CLIENTE"],
                "fono" => ["label" => "FONO"],
                "producto" => ["label" => "PRODUCTO"],
                "plan_producto" => ["label" => "PLAN_PRODUCTO"],
                "sales_person_name" => ["label" => "SALES_PERSON_NAME"],
                "pdv_desc" => ["label" => "PDV_DESC"],
                "pdv_razon_social_desc" => ["label" => "PDV_RAZON_SOCIAL_DESC"],
                "pdv_channel_desc" => ["label" => "PDV_CHANNEL_DESC"],
                "canal" => ["label" => "CANAL"],
                "sales_reason_desc" => ["label" => "SALES_REASON_DESC"],
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
            $request->input('periodo')
        )->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke(
            $request->input('type'),
            $request->input('dni'),
            $request->input('fono'),
            $request->input('periodo'),
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
