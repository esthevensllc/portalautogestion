<?php

namespace AMovil\Reports\DAPU\BusquedaSimCard\Controllers;

use AMovil\Reports\DAPU\BusquedaSimCard\Services\BusquedaSimCardExporter;
use AMovil\Reports\DAPU\BusquedaSimCard\Services\BusquedaSimCardFinder;
use Illuminate\Http\Request;

class BusquedaSimCardController
{
    private $finder;
    private $export;

    public function __construct(BusquedaSimCardFinder $finder, BusquedaSimCardExporter $export)
    {
        $this->finder = $finder;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "BUSQUEDA SIM CARD",
            "url" => asset("dapu/busqueda-sim-card/json"),
            "url_export" => asset("dapu/busqueda-sim-card/export"),
            "fields" => [
                "fecha_venta" => ["label" => "FECHA_VENTA"],
                "imei" => ["label" => "IMEI"],
                "codigo_chip" => ["label" => "CODIGO_CHIP"],
                "codigo_equipo" => ["label" => "CODIGO_EQUIPO"],
                "descripcion_equipo" => ["label" => "DESCRIPCION_EQUIPO"],
                "equipo" => ["label" => "EQUIPO"],
                "linea" => ["label" => "LINEA"],
                "modalidad" => ["label" => "MODALIDAD"],
                "canal" => ["label" => "CANAL"],
                "desc_oficina_venta" => ["label" => "DESC_OFICINA_VENTA"],
                "tipo" => ["label" => "TIPO"],
                "tipo_doc_cliente" => ["label" => "TIPO_DOC_CLIENTE"],
                "documento_cliente" => ["label" => "DOCUMENTO_CLIENTE"],
                "transaccion" => ["label" => "TRANSACCION"],
                "nombres_cliente" => ["label" => "NOMBRES_CLIENTE"],
                "apellidos_cliente" => ["label" => "APELLIDOS_CLIENTE"],
                "cod_vendedor" => ["label" => "COD_VENDEDOR"],
                "nombre_vendedor" => ["label" => "NOMBRE_VENDEDOR"],
                "nro_contrato" => ["label" => "NRO_CONTRATO"],
            ],
                "form_view" => "dapu.busqueda_sim_card_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = $this->finder->__invoke(
            $request->input('iccid'),
        )->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke(
            $request->input('iccid'),
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
