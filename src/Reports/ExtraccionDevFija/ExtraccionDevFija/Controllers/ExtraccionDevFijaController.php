<?php

namespace AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Controllers;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaFilterFlag;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\ExportExtraccionFijaPostpago;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\ExportExtraccionFijaUsuariosAfectados;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\FindReportInputs;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\GetReportInputs;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\ProcessExtraccionDevFija;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\ProcessExtraccionDevFijaMsisdn;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\UploadReportExtraccionFija;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Services\InformeFallasFinder;
use AMovil\Reports\ExtraccionDevFija\TicketReports\Services\FijaTicketReportFinder;
use AMovil\Shared\Application\FileInput;
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
    private $reporteExtraccionUploader;
    private $finder;
    private $processExtraccionMsisdn;

    public function __construct(
        ProcessExtraccionDevFija $process,
        GetReportInputs $getReportInputs,
        FindReportInputs $findReportInputs,
        ExportExtraccionFijaUsuariosAfectados $exportUsuarioAfectados,
        ExportExtraccionFijaPostpago $exportRepPostpago,
        FijaTicketReportFinder $ticketReportFinder,
        InformeFallasFinder $informeFallasFinder,
        UploadReportExtraccionFija $reporteExtraccionUploader,
        InformeFallasFinder $finder,
        ProcessExtraccionDevFijaMsisdn $processExtraccionMsisdn
    ) {
        $this->process = $process;
        $this->getReportInputs = $getReportInputs;
        $this->findReportInputs = $findReportInputs;
        $this->exportUsuarioAfectados = $exportUsuarioAfectados;
        $this->exportRepPostpago = $exportRepPostpago;
        $this->ticketReportFinder = $ticketReportFinder;
        $this->informeFallasFinder = $informeFallasFinder;
        $this->reporteExtraccionUploader = $reporteExtraccionUploader;
        $this->finder = $finder;
        $this->processExtraccionMsisdn = $processExtraccionMsisdn;
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
        
        $this->process->processAndGetGruposUsuario(
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
        $response = $this->process->processEnd(
            $request->input("ticket"),
            1,
            ExtraccionDevFijaFilterFlag::NONE
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

    
    public function cargaReporteView()
    {
        $ticketReports = $this->ticketReportFinder->getByCriteria([])["data"];
        $config = [
            "title" => "CARGA DE REPORTES",
            "api" => url("extraccion-dev-fija/carga-reportes/upload"),
            "tickets" => $ticketReports,
        ];
        return view("extraccion_dev_fija.carga_reportes", compact("config"));
    }

    public function uploadReport(Request $request)
    {   
        $file = new FileInput(
            $request->file("excel")->getPathname(),
            $request->file("excel")->getClientOriginalName()
        );
        $this->reporteExtraccionUploader->__invoke(
            $request->input("ticket"),
            $request->input("departamento"),
            $file
        );
        return response()->json([]);
    }

    public function processByMsisdnView()
    {
        $config = [
            'title' => 'TRABAJO DE MANTENIMIENTO',
            'api' => url('extraccion-dev-fija/msisdn/process'),
            // 'tableTitle' => 'INFORMES DE FALLA',
            "servicio_afectado" => $this->finder->getServiciosAfectados(),
            "compensaciones" => json_decode(json_encode([
                ["label" => "No aplica", "id" => "0"],
                ["label" => "Sí aplica", "id" => "1"]
            ])),
        ];
        return view("extraccion_dev_fija.process_extraccion_msisdn", compact("config"));
    }

    public function processByMsisdn(Request $request)
    {
        $serviciosAfectados = $request->input("servicio_afectado_id");
        $fechasIni = $request->input("fecha_ini");
        $horasIni = $request->input("hora_ini");
        $fechasFin = $request->input("fecha_fin");
        $horasFin = $request->input("hora_fin");
        $compensaciones = $request->input("compensacion_id");
        $tickets = $request->input("ticket");

        $detalleServicios = [];
        foreach($serviciosAfectados as $index => $row){
            $detalleServicios[] = [
                "servicioAfectadoId" => $serviciosAfectados[$index],
                "fechaIni" => $fechasIni[$index],
                "horaIni" => $horasIni[$index],
                "fechaFin" => $fechasFin[$index],
                "horaFin" => $horasFin[$index],
                "compensacionId" => $compensaciones[$index],
                "ticket" => $tickets[$index],
            ];
        }

        $excelFile = new FileInput(
            $request->file("excel")->getPathname(),
            $request->file("excel")->getClientOriginalName()
        );

        $response = $this->processExtraccionMsisdn->__invoke(
            $request->input("numero_reporte"),
            $excelFile,
            $excelFile,
            // $request->input("ticket"),
            $detalleServicios,
            ExtraccionDevFijaFilterFlag::NONE,
            $request->ip()
            // $request->input("servicio_afectado_id"),
            // $request->input("fecha_ini"),
            // $request->input("hora_ini"),
            // $request->input("fecha_fin"),
            // $request->input("hora_fin"),
            // $request->input("compensacion_id")
        );
        if ($response->fails()) {
            return response()->json($response->errors(), 400);
        }
        return response()->json($response->data());
    }
}
