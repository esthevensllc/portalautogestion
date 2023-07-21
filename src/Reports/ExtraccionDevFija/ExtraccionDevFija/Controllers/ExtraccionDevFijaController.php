<?php

namespace AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Controllers;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\ProcessExtraccionDevFija;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExtraccionDevFijaController
{
    private $process;
    public function __construct(ProcessExtraccionDevFija $process)
    {
        $this->process = $process;
    }

    public function view()
    {
        $config = [
            "title" => "EXTRACCIÓN Y DEVOLUCIÓN FIJA",
            "url" => asset("extraccion-dev-fija/process"),
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
            "servicio_afectado" => DB::table("usraes.SA_DEVOLUCION_EQUIVALENCIAS")
            ->select("OSIPTEL")
            ->groupBy("OSIPTEL")
            ->orderBy("OSIPTEL")
            ->get()
        ];

        return view("extraccion_dev_fija.extraccion_dev_fija", compact("config"));
    }

    public function process(Request $request)
    {
        ini_set('max_execution_time', '7200');
        
        $response = $this->process->__invoke(
            $request->input("departamento"),
            $request->input("provincia"),
            $request->input("distrito"),
            $request->input("plano"),
            $request->input("ticket"),
            $request->input("servicio_afectado"),
            $request->input("fecha_ini"),
            $request->input("hora_ini"),
            $request->input("fecha_fin"),
            $request->input("hora_fin"),
            $request->input("meses_interes")
        )->data();
        
        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
        
        //return response()->json([]);
    }
}
