<?php

namespace AMovil\Reports\DAPU\LogNoBiometria\Controllers;

use AMovil\Reports\DAPU\LogNoBiometria\Services\ExportLogNoBiometria;
use AMovil\Reports\DAPU\LogNoBiometria\Services\GetLogNoBiometria;
use Illuminate\Http\Request;

class LogNoBiometriaController
{
    private $get;
    private $export;

    public function __construct(GetLogNoBiometria $get, ExportLogNoBiometria $export)
    {
        $this->get = $get;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "LOG DE NO BIOMETRIA",
            "url" => asset("dapu/log-no-biometria/json"),
            "url_export" => asset("dapu/log-no-biometria/export"),
            "fields" => [
                "biom_tipovalidacion" => ["label" => "BIOM_TIPOVALIDACION"],
                "biom_idpadre" => ["label" => "BIOM_IDPADRE"],
                "biom_rptaval" => ["label" => "BIOM_RPTAVAL"],
                "biom_aplicacion" => ["label" => "BIOM_APLICACION"],
                "biom_nrodocumento" => ["label" => "BIOM_NRODOCUMENTO"],
                "biom_observacion" => ["label" => "BIOM_OBSERVACION"],
                "biom_fecha_crea" => ["label" => "BIOM_FECHA_CREA"],
                "biom_codigo" => ["label" => "BIOM_CODIGO"],
            ],
            "form_view" => "dapu.log_no_biometria_form",
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
