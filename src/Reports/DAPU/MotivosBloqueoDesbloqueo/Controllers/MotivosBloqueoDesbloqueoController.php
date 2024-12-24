<?php

namespace AMovil\Reports\DAPU\MotivosBloqueoDesbloqueo\Controllers;

use AMovil\Reports\DAPU\MotivosBloqueoDesbloqueo\Services\ExportMotivosBloqueoDesbloqueo;
use AMovil\Reports\DAPU\MotivosBloqueoDesbloqueo\Services\MotivosBloqueoDesbloqueoFinder;
use Illuminate\Http\Request;

class MotivosBloqueoDesbloqueoController
{
    private $get;
    private $export;

    public function __construct(MotivosBloqueoDesbloqueoFinder $get, ExportMotivosBloqueoDesbloqueo $export)
    {
        $this->get = $get;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "MOTIVOS DE BLOQUEO Y DESBLOQUEO",
            "url" => asset("dapu/motivos-bloqueo-desbloqueo/json"),
            "url_export" => asset("dapu/motivos-bloqueo-desbloqueo/export"),
            "fields" => [
                "id_termovimiento" => ["label" => "ID_TERMOVIMIENTO"],
                "numero_imei" => ["label" => "IMEI"],
                "ter_numerolinea" => ["label" => "TER_NUMEROLINEA"],
                "ter_reportante" => ["label" => "TER_REPORTANTE"],
                "ter_asesor_servicio" => ["label" => "TER_ASESOR_SERVICIO"],
                "ter_fecregistro" => ["label" => "TER_FECREGISTRO"],
                "ter_estado" => ["label" => "TER_ESTADO"],
                "ter_des_motivo" => ["label" => "TER_DES_MOTIVO"],
                "ter_marca_modelo" => ["label" => "TER_MARCA_MODELO"],
            ],
            "form_view" => "dapu.motivos_bloqueo_desb_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = $this->get->__invoke($request->input('imei'))->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke(
            $request->input('type'),
            $request->input('imei'),
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
