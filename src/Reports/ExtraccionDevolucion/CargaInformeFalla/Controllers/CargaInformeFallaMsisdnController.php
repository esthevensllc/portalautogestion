<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\InformeTipoReporte;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\CargarReporte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CargaInformeFallaMsisdnController
{
    private $cargarReporte;
    
    public function __construct(
        CargarReporte $cargarReporte,
    ){
        $this->cargarReporte = $cargarReporte;
    }

    public function view()
    {
        $config = [
            "title" => "INFORME DE FALLAS - MSISDN",
            "api" => asset("extraccion-devolucion/carga-informe-fallas-msisdn/import"),
            "downloadApi" => asset("extraccion-devolucion/carga-informe-fallas-msisdn/[numReporte]/download"),
            "fileFormat" => ".csv",
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
            ->get()
        ];
        return view("extraccion_devolucion.carga_informe_falla", compact("config"));
    }

    public function import(Request $request)
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

        $detalleExtraccion = [];
        $cell_2g = $request->input("cell2g", []);
        $cell_3g = $request->input("cell3g", []);
        $cell_4g = $request->input("cell4g", []);
        $distritos = $request->input("distritos", []);
        $corteFechaIni = $request->input("corteFechaIni", []);
        $corteHoraIni = $request->input("corteHoraIni", []);
        $corteFechaFin = $request->input("corteFechaFin", []);
        $corteHoraFin = $request->input("corteHoraFin", []);
        foreach($distritos as $i => $distlist){
            $strCeldas = [];
            if($cell_2g[$i] !== null){
                $strCeldas[] = $cell_2g[$i];
            }
            if($cell_3g[$i] !== null){
                $strCeldas[] = $cell_3g[$i];
            }
            if($cell_4g[$i] !== null){
                $strCeldas[] = $cell_4g[$i];
            }
            $detalleExtraccion[] = [
                "celdas" => implode(",", $strCeldas),
                "distritos" => $distlist,
                "corteFechaIni" => $corteFechaIni[$i]." ".$corteHoraIni[$i],
                "corteFechaFin" => $corteFechaFin[$i]." ".$corteHoraFin[$i]
            ];
        }

        $reporte = $this->cargarReporte->__invoke(
            $request->input('numero_reporte'),
            InformeTipoReporte::BY_MSISDN,
            $request->file('excel'),
            $detalleExtraccion,
            $request->ip()
            /*implode(",", $strCeldas),
            $request->input("provincias"),
            $request->input('corte_fecha1_date')." ".$request->input('corte_fecha1_time'),
            $request->input('corte_fecha2_date')." ".$request->input('corte_fecha2_time')*/
        );
        return response()->json(["result" => $reporte]);
    }
}
