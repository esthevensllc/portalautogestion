<?php

namespace AMovil\Reports\DAPU\ConsultaImei\Controllers;

use AMovil\Reports\DAPU\ConsultaImei\Services\ExportConsultaImei;
use AMovil\Reports\DAPU\ConsultaImei\Services\ImeiFinder;
use Illuminate\Http\Request;

class ConsultaImeiController
{
    private ImeiFinder $finder;
    private ExportConsultaImei $export;

    public function __construct(ImeiFinder $finder, ExportConsultaImei $export)
    {
        $this->finder = $finder;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "CONSULTA IMEI",
            "url" => asset("dapu/consulta-imei/json"),
            "url_export" => asset("dapu/consulta-imei/export"),
            "fields" => [
                "msisdn" => ["label" => "MSISDN"],
                "nombres" => ["label" => "NOMBRES"],
                "ape_paterno" => ["label" => "APE_PATERNO"],
                "ape_materno" => ["label" => "APE_MATERNO"],
                "nro_documento" => ["label" => "NRO_DOCUMENTO"],
                "razon_social" => ["label" => "RAZON_SOCIAL"],
                "imsi" => ["label" => "IMSI"],
                "imei" => ["label" => "IMEI"],
                "fecha_actualizacion" => ["label" => "FECHA_ACTUALIZACION"],
            ],
            "form_view" => "dapu.consulta_imei_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = $this->finder->__invoke(
            $request->input('msisdn'),
            $request->input('fecha')
        )->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke(
            $request->input('msisdn'),
            $request->input('fecha'),
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
