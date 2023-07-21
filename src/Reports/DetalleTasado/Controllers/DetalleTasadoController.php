<?php

namespace AMovil\Reports\DetalleTasado\Controllers;

use AMovil\Reports\DetalleTasado\Services\ExportDetalleTasado;
use Illuminate\Http\Request;

class DetalleTasadoController
{
    private $export;
    public function __construct(ExportDetalleTasado $export)
    {
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "DETALLE TASADO",
            "url" => asset("rep-det-consumo/detalle-tasado/export"),
        ];
        return view("detalle_tasado.detalle_tasado", compact("config"));
    }

    public function export(Request $request)
    {
        ini_set('max_execution_time', '7200');
        set_time_limit(7200);
        
        $response = $this->export->__invoke(
            $request->input("numero_cuenta"),
            $request->input("tipo_input"),
            $request->input("fecha"),
            $request->input("fecha_ini"),
            $request->input("fecha_fin")
        )->data();

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
    }
}
