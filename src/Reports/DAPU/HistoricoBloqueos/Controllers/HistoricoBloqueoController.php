<?php

namespace AMovil\Reports\DAPU\HistoricoBloqueos\Controllers;

use AMovil\Reports\DAPU\HistoricoBloqueos\Services\ExportHistoricoBloqueo;
use AMovil\Reports\DAPU\HistoricoBloqueos\Services\GetHistoricoBloqueo;
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
                "imei" => ["label" => "IMEI"],
                "fecha_registro" => ["label" => "FECHA_REGISTRO"],
                "linea" => ["label" => "LINEA"],
                "ter_estado" => ["label" => "TER_ESTADO"],
                "ter_des_motivo" => ["label" => "TER_DES_MOTIVO"],
            ],
            "form_view" => "dapu.historico_bloqueos_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = $this->get->__invoke(
            $request->input('imei')
        )->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke(
            $request->input('type'),
            $request->input('imei')
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
