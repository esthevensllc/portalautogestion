<?php

namespace AMovil\Reports\OltCmts\Controllers;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaFilterFlag;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\ProcessExtraccionDevFijaMsisdn;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Services\InformeFallasFinder;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Services\InformeFallasUpdater;
use AMovil\Reports\OltCmts\Services\ExportOltCmtsNuevoReport;
use AMovil\Reports\OltCmts\Services\OltCmtsFinder;
use AMovil\Shared\Application\FileInput;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OltCmtsNuevoController
{
    private $finder;
    private $exporter;
    private $processExtraccionMsisdn;
    private $informeFinder;
    private $informeFallaUpdater;

    public function __construct(OltCmtsFinder $finder, ExportOltCmtsNuevoReport $exporter, ProcessExtraccionDevFijaMsisdn $processExtraccionMsisdn, InformeFallasFinder $informeFinder, InformeFallasUpdater $informeFallaUpdater)
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
        $typeId = (string) $request->input("estado", $request->input("type_id"));
        $data = [];
        switch ($typeId) {
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

        try {
            $response = $this->exporter->exportFilteredFinal(
                (int) $request->input("type_id"),
                $request->input("fecha"),
                $request->input("values", [])
            )->data();

            return response($response["content"], 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="'. $response["filename"] .'"'
            ]);
        } catch (\Throwable $th) {
            Log::error('OLT/CMTS export final filtrado falló', [
                'user' => optional(auth()->user())->username,
                'type_id' => $request->input('type_id'),
                'fecha' => $request->input('fecha'),
                'values_count' => count((array) $request->input('values', [])),
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'No se pudo generar el reporte OLT/CMTS filtrado. Las tablas temporales del usuario fueron limpiadas.',
                'error' => $th->getMessage(),
            ], 500);
        } finally {
            try {
                $this->exporter->cleanupTemporaryTables();
            } catch (\Throwable $cleanupError) {
                Log::error('OLT/CMTS no pudo limpiar tablas temporales del usuario', [
                    'user' => optional(auth()->user())->username,
                    'message' => $cleanupError->getMessage(),
                    'trace' => $cleanupError->getTraceAsString(),
                ]);
            }
        }
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
