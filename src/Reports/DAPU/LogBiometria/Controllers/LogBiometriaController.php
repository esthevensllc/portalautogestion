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
                "dni" => ["label" => "DNI"],
                "fecha_registro_transaccion" => ["label" => "FECHA_REGISTRO_TRANSACCION"],
                "mensaje_respuesta" => ["label" => "MENSAJE_RESPUESTA"],
                "modelo_dispositivo" => ["label" => "MODELO_DISPOSITIVO"],
                "marca_dispositivo" => ["label" => "MARCA_DISPOSITIVO"],
                "version_aplicativo" => ["label" => "VERSION_APLICATIVO"],
                "codigo_aplicativo" => ["label" => "CODIGO_APLICATIVO"],
                "modelo_estacion" => ["label" => "MODELO_ESTACION"],
                "codigo_identi_estacion" => ["label" => "CODIGO_IDENTI_ESTACION"],
            ],
            "form_view" => "dapu.log_biometria_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = $this->get->__invoke(
            $request->input('dni'),
            $request->input('periodo')
        )->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke(
            $request->input('type'),
            $request->input('dni'),
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
