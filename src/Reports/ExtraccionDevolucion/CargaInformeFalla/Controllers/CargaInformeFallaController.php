<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\InformeTipoReporte;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\CargarReporte;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\GetReports;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\FindReportInput;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\ExportReport;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\DeleteReport;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\AprobarReport;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\DesaprobarReport;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\EnEsperaReport;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\RevisadoReport;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\UpdateReportStatus;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ExportExtraccion;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ExportMontoDevolucion;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ExportRepPostpago;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ExportRepPrepago;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ExportUsuarioAfectados;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ProcessExtraccion;
use AMovil\Reports\ExtraccionDevolucion\TicketReports\Services\GetTicketReports;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\GetReportesByCriteria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CargaInformeFallaController
{
    private $cargarReporte;
    private $getReports;
    private $findReportInput;
    private $export;
    private $deleteReport;
    private $aprobarReport;
    private $desaprobarReport;
    private $enEsperaReport;
    private $revisadoReport;
    private $updateReportStatus;
    private $service;
    private $exportRepMontoDevolucion;
    private $processExtraccion;
    private $exportUsuarioAfectados;
    private $exportRepPostpago;
    private $exportRepPrepago;
    private $getTicketReports;
    private $getReportesByCriteria;

    public function __construct(
        CargarReporte $cargarReporte,
        GetReports $getReports,
        FindReportInput $findReportInput,
        ExportReport $export,
        DeleteReport $deleteReport,
        AprobarReport $aprobarReport,
        DesaprobarReport $desaprobarReport,
        EnEsperaReport $enEsperaReport,
        RevisadoReport $revisadoReport,
        UpdateReportStatus $updateReportStatus,
        ExportExtraccion $service,
        ExportMontoDevolucion $exportRepMontoDevolucion,
        ProcessExtraccion $processExtraccion,
        ExportUsuarioAfectados $exportUsuarioAfectados,
        ExportRepPostpago $exportRepPostpago,
        ExportRepPrepago $exportRepPrepago,
        GetTicketReports $getTicketReports,
        GetReportesByCriteria $getReportesByCriteria,
    ){
        $this->service = $service;
        $this->getReports = $getReports;
        $this->findReportInput = $findReportInput;
        $this->export = $export;
        $this->deleteReport = $deleteReport;
        $this->aprobarReport = $aprobarReport;
        $this->desaprobarReport = $desaprobarReport;
        $this->enEsperaReport = $enEsperaReport;
        $this->revisadoReport = $revisadoReport;
        $this->updateReportStatus = $updateReportStatus;
        $this->exportRepMontoDevolucion = $exportRepMontoDevolucion;
        $this->processExtraccion = $processExtraccion;
        $this->exportUsuarioAfectados = $exportUsuarioAfectados;
        $this->exportRepPostpago = $exportRepPostpago;
        $this->exportRepPrepago = $exportRepPrepago;
        $this->cargarReporte = $cargarReporte;
        $this->getTicketReports = $getTicketReports;
        $this->getReportesByCriteria = $getReportesByCriteria;
    }

    public function view()
    {
        $config = [
            "title" => "CARGAR INFORME DE FALLAS",
            "api" => asset("extraccion-devolucion/carga-informe-fallas/import"),
            "downloadApi" => asset("extraccion-devolucion/carga-informe-fallas/[numReporte]/download"),
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
            InformeTipoReporte::DEFAULT,
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

    public function get(){
        $response = $this->getReports->__invoke();
        return response()->json($response);
    }

    public function findInput($id)
    {
        $response = $this->findReportInput->__invoke($id);
        return response()->json($response);
    }

    public function getReportesByTicket($ticket){
        $reports = $this->getReportesByCriteria->__invoke([
            ["ticket", $ticket],
        ])->data();
        return response()->json(["data" => $reports]);
    }

    public function reportView($id)
    {
        $content = $this->export->__invoke(
            $id
        );

        return response($content["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$content["filename"].'"'
        ]);
    }

    public function delete($id)
    {
        $response = $this->deleteReport->__invoke($id);
        return response()->json($response);
    }

    public function aprobar(Request $request)
    {
        $response = $this->aprobarReport->__invoke($request->input('id'),$request->input('ticket'), $request->ip());
        return response()->json(["result" => $response]);
    }

    public function desaprobar(Request $request)
    {
        $response = $this->desaprobarReport->__invoke($request->input('id'), $request->ip());
        return response()->json(["result" => $response]);
    }

    public function enEspera(Request $request)
    {
        $response = $this->enEsperaReport->__invoke($request->input('id'), $request->ip());
        return response()->json(["result" => $response]);
    }

    public function revisado(Request $request)
    {
        $response = $this->revisadoReport->__invoke($request->input('id'), $request->ip());
        return response()->json(["result" => $response]);
    }

    public function updateReportStatus(Request $request)
    {
        $this->updateReportStatus->__invoke($request->input('status'), $request->input('id'), $request->ip());
        return response()->json(["passes" => true]);
    }

    public function download($numReporte)
    {
        $response = $this->findReportInput->downloadInformeFalla($numReporte);
        if ($response->fails()) {
            return response($response->errors(), 404);
        }
        
        $response = $response->data();
        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
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
            $request->input("fecha_interes"),
            $request->input('corte_fecha1_date')." ".$request->input('corte_fecha1_time'),
            $request->input('corte_fecha2_date')." ".$request->input('corte_fecha2_time'),
            $request->input("minutos_usuarios"),
            $request->ip()
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
}
