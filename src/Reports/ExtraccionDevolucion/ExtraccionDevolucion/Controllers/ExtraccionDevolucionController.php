<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Controllers;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\CargarReporte;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ExportExtraccion;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ExportMontoDevolucion;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ExportReporteMsisdn;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ExportRepPostpago;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ExportRepPrepago;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ExportUsuarioAfectados;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ProcessExtraccion;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ProcessExtraccionMsisdn;
use AMovil\Reports\ExtraccionDevolucion\TicketReports\Services\GetTicketReports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ExtraccionDevolucionController
{
    private $service;
    private $exportRepMontoDevolucion;
    private $processExtraccion;
    private $exportUsuarioAfectados;
    private $exportRepPostpago;
    private $exportRepPrepago;
    private $exportRepMsisdn;
    private $cargarReporte;
    private $getTicketReports;
    private $processExtraccionMsisdn;

    public function __construct(
        ExportExtraccion $service,
        ExportMontoDevolucion $exportRepMontoDevolucion,
        ProcessExtraccion $processExtraccion,
        ExportUsuarioAfectados $exportUsuarioAfectados,
        ExportRepPostpago $exportRepPostpago,
        ExportRepPrepago $exportRepPrepago,
        ExportReporteMsisdn $exportRepMsisdn,
        CargarReporte $cargarReporte,
        GetTicketReports $getTicketReports,
        ProcessExtraccionMsisdn $processExtraccionMsisdn,
    ){
        $this->service = $service;
        $this->exportRepMontoDevolucion = $exportRepMontoDevolucion;
        $this->processExtraccion = $processExtraccion;
        $this->exportUsuarioAfectados = $exportUsuarioAfectados;
        $this->exportRepPostpago = $exportRepPostpago;
        $this->exportRepPrepago = $exportRepPrepago;
        $this->exportRepMsisdn = $exportRepMsisdn;
        $this->cargarReporte = $cargarReporte;
        $this->getTicketReports = $getTicketReports;
        $this->processExtraccionMsisdn = $processExtraccionMsisdn;
    }

    public function view()
    {
        $config = [
            "url" => asset("extraccion-devolucion/process"),
            "title" => "Extracción",
            "tipo_input" => [
                ["id" => 1, "label" => "Manual"],
                ["id" => 2, "label" => "Excel"],
            ],
            "tipo_reporte" => [
                ["id" => 1, "label" => "USUARIOS AFECTADOS"],
                ["id" => 2, "label" => "MONTO A DEVOLVER"],
            ],
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
        return view("extraccion_devolucion.extraccion_devolucion", compact("config"));
    }

    public function reportView()
    {
        $tickets = $this->getTicketReports->getTickets();
        $departamentos = $this->getTicketReports->getDepartamentos();

        $config = [
            "title" => "DESCARGA DE REPORTES",
            "url" => asset("extraccion-devolucion/reportes/export"),
            "confirmApi" => asset("extraccion-devolucion/reportes/confirm"),
            "inputsApi" => asset("extraccion-devolucion/reportes/find-input"),
            "tickets" => $tickets,
            "departamentos" => $departamentos,
        ];
        return view("extraccion_devolucion.descarga_reportes", compact("config"));
    }

    public function updateView()
    {
        $tickets = $this->getTicketReports->getTickets();
        $departamentos = $this->getTicketReports->getDepartamentos();
        $config = [
            "title" => "CARGA DE REPORTES",
            "api" => asset("extraccion-devolucion/carga-reportes/import"),
            "tipo_input" => [
                ["id" => 1, "label" => "Postpago"],
                ["id" => 2, "label" => "Prepago"],
            ],
            "tickets" => $tickets,
            "departamentos" => $departamentos,
        ];
        return view("extraccion_devolucion.carga_reportes", compact("config"));
    }

    public function import(Request $request)
    {
        $this->cargarReporte->__invoke(
            $request->input('ticket'),
            $request->input('departamento'),
            $request->file('excel'),
            $request->input('tipo')
        );
        return response()->json([]);
    }

    public function process(Request $request)
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

        $resp = $this->processExtraccion->__invoke(
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

        if($request->input("tipo_reporte") === "1"){
            $response = $this->service->__invoke(
                $request->input("tipo_input"),
                implode(",", $strCeldas),
                $request->input("provincias"),
                $request->file('excel'),
                $request->input('fecha1_date')." ".$request->input('fecha1_time'),
                $request->input('fecha2_date')." ".$request->input('fecha2_time'),
                $request->input("ticket_osiptel"),
                $request->input("fecha_interes"),
                $request->input('corte_fecha1_date')." ".$request->input('corte_fecha1_time'),
                $request->input('corte_fecha2_date')." ".$request->input('corte_fecha2_time'),
            )->data();

            return response($response["content"], 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
            ]);
        }else{
            $response = $this->exportRepMontoDevolucion->__invoke(
                $request->input("ticket_osiptel"),
                $request->input("fecha_interes"),
                $request->input('corte_fecha1_date')." ".$request->input('corte_fecha1_time')
            )->data();
            
            return response($response["content"], 200, [
                'Content-Type' => 'application/zip; charset=UTF-8',
                'Content-Transfer-Encoding' => 'Binary',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
            ]);
        }
    }

    public function export(Request $request)
    {
        $tipoReporte = $request->input("tipo_reporte");
        $ticket = $request->input("ticket");
        $departamento = $request->input("departamento");

        if($tipoReporte === "1"){
            $response = $this->exportUsuarioAfectados->__invoke($ticket, $departamento)->data();
            return response($response["content"], 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
            ]);
        }else if ($tipoReporte === "2"){
            $response = $this->exportRepPostpago->__invoke($ticket, $departamento)->data();
            return response($response["content"], 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
            ]);
        }else{
            $response = $this->exportRepPrepago->__invoke($ticket, $departamento)->data();
            return response($response["content"], 200, [
                'Content-Encoding' => 'UTF-8',
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
            ]);
        }
    }

    public function exportReporteMsisdn(Request $request){
        $ticket = $request->input("ticket");
        $response = $this->exportRepMsisdn->__invoke($ticket)->data();
        return Response::stream($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
    }

    public function processByMsisdnView()
    {
        $tickets = $this->getTicketReports->getTickets();
        $departamentos = $this->getTicketReports->getDepartamentos();
        $config = [
            "title" => "DEVOLUCIONES EXCEPCIONALES",
            "api" => asset("extraccion-devolucion/msisdn/process"),
        ];
        return view("extraccion_devolucion.process_extraccion_msisdn", compact("config"));
    }

    public function processByMsisdn(Request $request){
        $response = $this->processExtraccionMsisdn->__invoke(
            $request->input("numero_reporte"),
            // $request->input("ticket"),
            $request->file("excel"),
            $request->input('corte_fecha1_date'), $request->input('corte_fecha1_time'),
            $request->input('corte_fecha2_date'), $request->input('corte_fecha2_time')
        );
        if ($response->fails()) {
            return response()->json($response->errors(), 400);
        }
        return response()->json($response->data());
    }
}
