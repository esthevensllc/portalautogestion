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
                "imei" => ["label" => "IMEI"],
                "linea" => ["label" => "LINEA"],
                "cod_asesor" => ["label" => "COD_ASESOR"],
                "fecha_transaccion" => ["label" => "FECHA_TRANSACCION"],
                "estado_transaccion" => ["label" => "ESTADO_TRANSACCION"],
                "motivo_transaccion" => ["label" => "MOTIVO_TRANSACCION"],
                "detalle_equipo" => ["label" => "DETALLE_EQUIPO"],
            ],
            "form_view" => "dapu.motivos_bloqueo_desb_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = $this->get->__invoke($request->input("tipo_input"), $this->getInputValue($request))->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke(
            $request->input("tipo_input"),
            $request->input('type'),
            $this->getInputValue($request),
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

    private function getInputValue(Request $request){
        $inputType = (int) $request->input("tipo_input");
        if ($inputType === 1) {
            return $request->input("linea");
        } else if ($inputType === 2) {
            return $request->input("imei");
        }
    }
}
