<?php

namespace AMovil\Reports\DAPU\LogBiometria\Controllers;

use AMovil\Reports\DAPU\LogBiometria\Services\ExportLogBiometria;
use AMovil\Reports\DAPU\LogBiometria\Services\GetLogBiometria;
use Illuminate\Http\Request;

class LogBiometriaController
{
    private $get;
    private $export;

    public function __construct(GetLogBiometria $get, ExportLogBiometria $export)
    {
        $this->get = $get;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "LOG DE BIOMETRIA",
            "url" => asset("dapu/log-biometria/json"),
            "url_export" => asset("dapu/log-biometria/export"),
            "fields" => [
                "biom_tipovalidacion" => ["label" => "BIOM_TIPOVALIDACION"],
                "biom_idpadre" => ["label" => "BIOM_IDPADRE"],
                "biom_codbio" => ["label" => "BIOM_CODBIO"],
                "biom_mensaje" => ["label" => "BIOM_MENSAJE"],
                "biom_aplicacion" => ["label" => "BIOM_APLICACION"],
                "biom_nrodocumento" => ["label" => "BIOM_NRODOCUMENTO"],
                "biom_nrotransac" => ["label" => "BIOM_NROTRANSAC"],
                "biom_fecha_crea" => ["label" => "BIOM_FECHA_CREA"],
                "biom_codigo" => ["label" => "BIOM_CODIGO"],
            ],
            "form_view" => "dapu.log_biometria_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = $this->get->__invoke(
            $request->input('dni'),
            $request->input('periodo_date').' '.$request->input('periodo_hour')
        )->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke(
            $request->input('type'),
            $request->input('dni'),
            $request->input('periodo_date').' '.$request->input('periodo_hour'),
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
