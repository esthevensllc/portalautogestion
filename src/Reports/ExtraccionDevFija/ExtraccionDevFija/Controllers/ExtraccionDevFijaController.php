<?php

namespace AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Controllers;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\ExportExtraccionFijaPostpago;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\ExportExtraccionFijaUsuariosAfectados;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\FindReportInputs;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\GetReportInputs;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\ProcessExtraccionDevFija;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Services\InformeFallasFinder;
use AMovil\Reports\ExtraccionDevFija\TicketReports\Services\FijaTicketReportFinder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExtraccionDevFijaController
{
    private $process;
    private $getReportInputs;
    private $findReportInputs;
    private $exportUsuarioAfectados;
    private $exportRepPostpago;
    private $ticketReportFinder;
    private $informeFallasFinder;

    public function __construct(
        ProcessExtraccionDevFija $process,
        GetReportInputs $getReportInputs,
        FindReportInputs $findReportInputs,
        ExportExtraccionFijaUsuariosAfectados $exportUsuarioAfectados,
        ExportExtraccionFijaPostpago $exportRepPostpago,
        FijaTicketReportFinder $ticketReportFinder,
        InformeFallasFinder $informeFallasFinder
    ) {
        $this->process = $process;
        $this->getReportInputs = $getReportInputs;
        $this->findReportInputs = $findReportInputs;
        $this->exportUsuarioAfectados = $exportUsuarioAfectados;
        $this->exportRepPostpago = $exportRepPostpago;
        $this->ticketReportFinder = $ticketReportFinder;
        $this->informeFallasFinder = $informeFallasFinder;
    }

    public function view()
    {
        $config = [
            "title" => "EXTRACCIÓN Y DEVOLUCIÓN",
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

    public function reportView()
    {
        $informesFalla = $this->informeFallasFinder->getProcesados()["data"];
        $ticketReports = $this->ticketReportFinder->getByCriteria([])["data"];
        $config = [
            "title" => "DESCARGA DE REPORTES",
            "url" => url("extraccion-dev-fija/reportes"),
            "findInputApi" => url("extraccion-dev-fija/reportes/find-input"),
            "exportApi" => url("extraccion-dev-fija/reportes/export"),
        ];
        return view("extraccion_dev_fija.descarga_reportes", compact("config", "informesFalla", "ticketReports"));
    }

    public function findInput(Request $request)
    {
        $input = $this->findReportInputs->__invoke($request->input("numero_reporte"), $request->input("ticket"))->data();
        $serviciosAfectados = $this->informeFallasFinder->getServiciosAfectados();
        return response()->json(["data" => $input, "serviciosAfectados" => $serviciosAfectados]);
    }

    public function process(Request $request)
    {
        ini_set('max_execution_time', '7200');
        
        $response = $this->process->__invoke(
            $request->input("departamento", []),
            $request->input("provincia", []),
            $request->input("distrito", []),
            $request->input("plano", []),
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
    }

    public function export(Request $request)
    {
        $tipoReporte = $request->input("tipo_reporte");
        // $numReporte = $request->input("numero_reporte");
        $ticket = $request->input("ticket");

        $response = null;
        if($tipoReporte === "1"){
            $response = $this->exportUsuarioAfectados->__invoke($ticket)->data();
        }else if ($tipoReporte === "2"){
            $response = $this->exportRepPostpago->__invoke($ticket)->data();
        }
        $headersByTipe = [
            "zip" => [
                'Content-Type' => 'application/zip; charset=UTF-8',
                'Content-Transfer-Encoding' => 'Binary',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
            ],
            "xlsx" => [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
            ]
            ];
        return response($response["content"], 200, $headersByTipe[$response["type"]]);
    }
}
