<?php

namespace AMovil\Reports\MantenimientoCeldas\Controllers;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ProcessExtraccion;
use AMovil\Reports\MantenimientoCeldas\Services\MantenimientoCeldaProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MantenimientoCeldaController
{
    private $processor;

    public function __construct(MantenimientoCeldaProcessor $processor)
    {
        $this->processor = $processor;
    }

    public function view()
    {
        $config = [
            "title" => "MANTENIMIENTO - CELDAS 2G-3G-4G",
            "url" => url("mantenimiento-celdas/process"),
            "confirmation" => false,
            "numero_reportes" => DB::table("usraes.noc_informe_de_fallas")
            ->select("numero_de_reporte")
            ->orderBy("numero_de_reporte")
            ->get(),
            "departamentos" => DB::table("usraes.cdr_celdas_red")
            ->select("departamento")
            ->groupBy("departamento")
            ->orderBy("departamento")
            ->get(),
            "provincias" => DB::table("usraes.cdr_celdas_red")
            ->select("departamento", "provincia")
            ->groupBy("departamento", "provincia")
            ->orderBy("provincia")
            ->get(),
            "distritos" => DB::table("usraes.cdr_celdas_red")
            ->select("departamento", "provincia", "distrito")
            ->groupBy("departamento", "provincia", "distrito")
            ->orderBy("distrito")
            ->get(),
            "ccpp" => DB::connection("ch-dn05")
            ->select("select departamento, provincia, distrito, ccpp, idubigeo ubigeo
            from portal_autogestion.zonas_inv
            group by departamento, provincia, distrito, ccpp, idubigeo")
        ];
        // return view("extraccion_devolucion.carga_informe_falla", compact("config"));
        return view("extraccion_devolucion.extraccion_devolucion", compact("config"));
    }
    
    function process(Request $request)
    {
        $strCeldas = [];
        $cell_2g = $request->input("cell_2g");
        $cell_3g = $request->input("cell_3g");
        $cell_4g = $request->input("cell_4g");
        if($cell_2g !== null){
            $strCeldas[] = $cell_2g;
        }
        if($cell_3g !== null){
            $strCeldas[] = $cell_3g;
        }
        if($cell_4g !== null){
            $strCeldas[] = $cell_4g;
        }

        $resp = $this->processor->__invoke(
            $request->input("step"),
            $request->input("tipo_input"),
            implode(",", $strCeldas),
            $request->input("provincias"),
            $request->file('excel'),
            $request->input('fecha1_date')." ".$request->input('fecha1_time'),
            $request->input('fecha2_date')." ".$request->input('fecha2_time'),
            $request->input("ticket_osiptel"),
            ProcessExtraccion::MESES_INTERES, // $request->input("fecha_interes"),
            $request->input('corte_fecha1_date')." ".$request->input('corte_fecha1_time'),
            $request->input('corte_fecha2_date')." ".$request->input('corte_fecha2_time'),
            ProcessExtraccion::MINUTOS_USUARIOS // $request->input("minutos_usuarios")
        )->data();

        return response()->json(["result" => $resp]);
    }
}
