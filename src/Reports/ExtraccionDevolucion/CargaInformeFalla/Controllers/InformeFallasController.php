<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers;

use Illuminate\Support\Facades\DB;

class InformeFallasController
{

    public function view()
    {
        $config = [
            "title" => "INFORMES DE FALLA",
            "numero_reportes" => DB::table("usraes.noc_informe_de_fallas")
            ->select("numero_de_reporte")
            ->orderBy("numero_de_reporte")
            ->get(),
        ];
        return view("extraccion_devolucion.informe_falla", compact("config"));
    }
}
