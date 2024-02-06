<?php

namespace AMovil\Reports\MantenimientoCeldas\Mantenimiento\Controllers;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ProcessExtraccion;
use AMovil\Reports\MantenimientoCeldas\Mantenimiento\Services\ExportMantenimientoCeldas;
use AMovil\Reports\MantenimientoCeldas\Mantenimiento\Services\MantenimientoCeldaProcessor;
use AMovil\Reports\MantenimientoCeldas\Mantenimiento\Services\MantenimientoCeldasInputFinder;
use AMovil\Reports\MantenimientoCeldas\TicketReports\Services\TicketReportFinder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MantenimientoCeldaController
{
    private $processor;
    private $finder;
    private $inputFinder;
    private $exporter;

    public function __construct(
        MantenimientoCeldaProcessor $processor,
        TicketReportFinder $finder,
        MantenimientoCeldasInputFinder $inputFinder,
        ExportMantenimientoCeldas $exporter
    ) {
        $this->processor = $processor;
        $this->finder = $finder;
        $this->inputFinder = $inputFinder;
        $this->exporter = $exporter;
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
        return view("mantenimiento_celdas.process_man_celdas", compact("config"));
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
            MantenimientoCeldaProcessor::MESES_INTERES, // $request->input("fecha_interes"),
            $request->input('corte_fecha1_date')." ".$request->input('corte_fecha1_time'),
            $request->input('corte_fecha2_date')." ".$request->input('corte_fecha2_time')
        )->data();

        return response()->json(["result" => $resp]);
    }

    public function export(Request $request)
    {
        $response = $this->exporter->__invoke(
            $request->post("ticket"),
            $request->post("departamento")
        )->data();
        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
    }

    public function reportsView()
    {
        $config = [
            "title" => "DESCARGA REPORTES",
            "url" => url("mantenimiento-celdas/reportes/export"),
            "inputsApi" => url("mantenimiento-celdas/reportes/input"),
            "tickets" => $this->finder->__invoke()
        ];
        return view("mantenimiento_celdas.descarga_reportes", compact("config"));
    }

    public function findInput(Request $request)
    {
        $data = $this->inputFinder->__invoke(
            $request->input("ticket"),
            $request->input("departamento")
        );
        return response()->json(["data" => $data]);
    }
}
