<?php

namespace AMovil\Reports\DAPU\HistoricoBloqueos\Controllers;

use AMovil\Reports\DAPU\HistoricoBloqueos\Services\ExportHistoricoBloqueo;
use AMovil\Reports\DAPU\HistoricoBloqueos\Services\GetHistoricoBloqueo;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;

class HistoricoBloqueoController
{
    private $get;
    private $export;

    public function __construct(GetHistoricoBloqueo $get, ExportHistoricoBloqueo $export)
    {
        $this->get = $get;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "HISTORICO BLOQUEOS",
            "url" => asset("dapu/historico-bloqueos/json"),
            "url_export" => asset("dapu/historico-bloqueos/export"),
            "fields" => [
                "histd_fecha_mensaje" => ["label" => "HISTD_FECHA_MENSAJE"],
                "imei" => ["label" => "IMEI"],
                "histn_num_servici" => ["label" => "HISTN_NUM_SERVICI"],
                "histv_imei" => ["label" => "HISTV_IMEI"],
                "histd_fecha_reporte" => ["label" => "HISTD_FECHA_REPORTE"],
                "nombre_apellidos" => ["label" => "NOMBRE_APELLIDOS"],
                "histn_tipo_documento" => ["label" => "HISTN_TIPO_DOCUMENTO"],
                "histv_numero_documento" => ["label" => "HISTV_NUMERO_DOCUMENTO"],
                "histv_tipo_solicitud" => ["label" => "HISTV_TIPO_SOLICITUD"],
                "histv_estado" => ["label" => "HISTV_ESTADO"],
                "histv_accion_realizar" => ["label" => "HISTV_ACCION_REALIZAR"],
            ],
            "form_method" => "POST",
            "form_view" => "dapu.historico_bloqueos_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = [];
        if((int) $request->input('tipo_input') === 1){
            $imeis = explode(",", str_replace(" ", "", $request->input('imei')));
            $response = $this->get->__invoke($imeis)->data();
        } else {
            $file = new FileInput(
                $request->file("file")->getPathname(),
                $request->file("file")->getClientOriginalName()
            );
            $response = $this->get->fromFile($file)->data();
        }
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = [];
        if((int) $request->input('tipo_input') === 1){
            $imeis = explode(",", str_replace(" ", "", $request->input('imei')));
            $response = $this->export->__invoke($request->input('type'), $imeis)->data();
        } else {
            $file = new FileInput(
                $request->file("file")->getPathname(),
                $request->file("file")->getClientOriginalName()
            );
            $response = $this->export->fromFile($request->input('type'), $file)->data();
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
}
