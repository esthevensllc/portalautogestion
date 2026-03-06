<?php

namespace AMovil\Reports\OltCmts\Controllers;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaFilterFlag;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\ProcessExtraccionDevFijaMsisdn;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Services\InformeFallasFinder;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Services\InformeFallasUpdater;
use AMovil\Reports\OltCmts\Services\ExportOltCmtsReport;
use AMovil\Reports\OltCmts\Services\OltCmtsFinder;
use AMovil\Shared\Application\FileInput;
use DateTime;
use Illuminate\Http\Request;

class OltCmtsController
{
    private $finder;
    private $exporter;
    private $processExtraccionMsisdn;
    private $informeFinder;
    private $informeFallaUpdater;

    public function __construct(OltCmtsFinder $finder, ExportOltCmtsReport $exporter, ProcessExtraccionDevFijaMsisdn $processExtraccionMsisdn, InformeFallasFinder $informeFinder, InformeFallasUpdater $informeFallaUpdater)
    {
        $this->finder = $finder;
        $this->exporter = $exporter;
        $this->processExtraccionMsisdn = $processExtraccionMsisdn;
        $this->informeFinder = $informeFinder;
        $this->informeFallaUpdater = $informeFallaUpdater;
    }
    
    public function view()
    {
        $config = [
            'title' => 'OLT',
            'url' => url('olt-cmts/export'),
            'searchApi' => url('olt-cmts/search'),
            'exportFinalApi' => url('olt-cmts/export-final'),
            'findValuesApi' => url('olt-cmts/find-values'),
            "types" => $this->finder->getReportTypes(),
            "values" => [],
            "oltSummary" => $this->finder->getOltListSummary(),
            "serviciosAfectados" => $this->informeFinder->getServiciosAfectados(),
        ];
        return view("olt_cmts.olt_cmts", compact("config"));
    }

    public function findValues(Request $request)
    {
        $fecha = DateTime::createFromFormat("Y-m-d", $request->get("fecha"));
        $data = [];
        switch ($request->get("type_id")) {
            case '1':
                $data = $this->finder->getOltsValues($fecha);
                break;
            case '2':
                $data = $this->finder->getCmtsValues($fecha);
                break;
            default:
                break;
        }
        return response()->json([
            "data" => $data
        ]);
    }

    public function export(Request $request)
    {
        ini_set('max_execution_time', '3600');

        $numeroReporte = $request->input("numero_reporte");

        $result = $this->informeFinder->__invoke(["numero_reporte.eq.{$numeroReporte}"]);
        if(count($result["data"]) > 0){
            return response()->json(["message" => "El informe de fallas '{$numeroReporte}' ya existe"], 400);
        }

        $response = $this->exporter->__invoke(
            $request->input("type_id"),
            $request->input("fecha_ini"),
            $request->input("values", [])
        )->data();
        $response['content'] = null;

        $now = new DateTime();
        $fechaIni = $request->input("fecha_ini");
        $horaIni = $request->input("hora_ini");
        $fechaFin = $request->input("fecha_fin");
        $horaFin = $request->input("hora_fin");
        $ticket = $now->format("YmdHis");

        $serviciosAfectados = ["1" => "13", "4" => "19", "6" => "26"];

        $detalleServicios = [];
        $tickets = [];
        foreach($serviciosAfectados as $index => $ticketInicial){
            $detalleServicios[] = [
                "servicioAfectadoId" => $index,
                "fechaIni" => $fechaIni,
                "horaIni" => $horaIni,
                "fechaFin" => $fechaFin,
                "horaFin" => $horaFin,
                "compensacionId" => 0,
                "ticket" => $ticket.$ticketInicial,
            ];
            $tickets[] = $ticket.$ticketInicial;
        }

        // $excelFile = new FileInput(
        //     $request->file("archivo")->getPathname(),
        //     $request->file("archivo")->getClientOriginalName()
        // );

        $clientesFile = new FileInput(
            $response["filepath"],
            $response["filename"],
        );

        $filterFlag = (string) $request->input("type_id") === '1' ? ExtraccionDevFijaFilterFlag::OLT : ExtraccionDevFijaFilterFlag::CMTS;

        $responseExtraccion = $this->processExtraccionMsisdn->__invoke(
            $numeroReporte,
            $clientesFile,
            $clientesFile,
            $detalleServicios,
            $filterFlag,
            $request->ip()
        );

        if ($responseExtraccion->fails()) {
            return response()->json($responseExtraccion->errors(), 400);
        }

        $responseFinal = $this->exporter->exportFinal(
            $request->input("type_id"),
            $tickets
        )->data();

        $responseFinalFile = new FileInput(
            $responseFinal["filepath"],
            "{$numeroReporte}.xlsx",
        );

        $this->informeFallaUpdater->updateNombreArchivo($numeroReporte, $responseFinalFile);

        return response($responseFinal["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $responseFinal["filename"] .'"'
        ]);
    }

    public function exportFinal(Request $request)
    {
        $tickets = explode(",", $request->input("ticket"));
        $response = $this->exporter->exportFinal(
            $request->input("type_id"),
            $tickets
        )->data();

        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $response["filename"] .'"'
        ]);
    }
}
