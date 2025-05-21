<?php

namespace AMovil\Reports\ExtraccionDevFija\InformesFalla\Controllers;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\FindReportInputs;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services\ProcessExtraccionDevFija;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Services\CreateInformeFallas;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Services\DeleteInformeFallas;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Services\InformeFallasFinder;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Services\InformeFallasUpdater;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Services\NotifyUsersOnInformeFallasProcessed;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InformeFallasController
{
    private $creator;
    private $finder;
    private $updater;
    private $deleter;
    private $findReportInputs;
    private $process;
    private $notifyOnProcessed;

    public function __construct(
        CreateInformeFallas $creator,
        InformeFallasFinder $finder,
        InformeFallasUpdater $updater,
        DeleteInformeFallas $deleter,
        FindReportInputs $findReportInputs,
        ProcessExtraccionDevFija $process,
        NotifyUsersOnInformeFallasProcessed $notifyOnProcessed
    ) {
        $this->creator = $creator;
        $this->finder = $finder;
        $this->updater = $updater;
        $this->deleter = $deleter;
        $this->findReportInputs = $findReportInputs;
        $this->process = $process;
        $this->notifyOnProcessed = $notifyOnProcessed;
    }

    public function view()
    {
        $serviciosAfectados = $this->finder->getServiciosAfectados();
        $config = [
            'title' => 'Informe de Fallas',
            'url' => url('extraccion-dev-fija/informes-falla/search'),
            'updateStatusApi' => url('extraccion-dev-fija/informes-falla/update-status'),
            'deleteApi' => url('extraccion-dev-fija/informes-falla/[numReporte]/[servicioAfectadoId]/delete'),
            'downloadApi' => url('extraccion-dev-fija/informes-falla/[numReporte]/download'),
            "serviciosAfectados" => $serviciosAfectados
        ];
        return view("extraccion_dev_fija.informes_falla", compact("config"));
    }

    public function search()
    {
        $response = $this->finder->__invoke();
        return response()->json($response);
    }

    public function updateStatus(Request $request)
    {
        $status = $request->input("status");
        $numReporte = $request->input("numero_reporte");
        $servicioAfectadoId = $request->input("servicio_afectado_id");
        $ticket = $request->input("ticket");
        switch ($status) {
            case 'revisado':
                $this->updater->updateStatusToRevisado($numReporte, $servicioAfectadoId);
                break;
            case 'aprobar':
                $this->updater->updateStatusToAprobado($numReporte, $servicioAfectadoId, $ticket);
                break;
            case 'desaprobar':
                $this->updater->updateStatusToDesaprobado($numReporte, $servicioAfectadoId);
                break;
            case 'enEspera':
                $this->updater->updateStatusToEnEspera($numReporte, $servicioAfectadoId);
                break;
            case 'enEjecucion':
                $this->updater->updateStatusToEnEjecucion($numReporte, $servicioAfectadoId);
                break;
            case 'enEsperaEjecucion':
                $this->updater->updateStatusToEnEsperaEjecucion($numReporte, $servicioAfectadoId);
                break;
            default:
                throw new Exception("Estado de informe de fallas no valido");
                break;
        }
        return response()->json([]);
    }

    public function delete($numReporte, $servicioAfectadoId)
    {
        $this->deleter->__invoke($numReporte, $servicioAfectadoId);
        return response()->json([]);
    }

    public function download($numReporte)
    {
        $response = $this->finder->downloadInformeFalla($numReporte)->data();
        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$response["filename"].'"'
        ]);
    }

    public function createView()
    {
        $config = [
            'title' => 'CARGA INFORME DE FALLAS',
            'createApi' => url('extraccion-dev-fija/informes-falla/cargar'),
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
            'tableTitle' => 'INFORMES DE FALLA',
            "servicio_afectado" => $this->finder->getServiciosAfectados(),
            "compensaciones" => json_decode(json_encode([
                ["label" => "No aplica", "id" => "0"],
                ["label" => "Sí aplica", "id" => "1"]
            ])),
            'searchApi' => url('extraccion-dev-fija/informes-falla/cargar/search'),
            'updateStatusApi' => url('extraccion-dev-fija/informes-falla/cargar/update-status'),
            'deleteApi' => url('extraccion-dev-fija/informes-falla/cargar/[numReporte]/[servicioAfectadoId]/delete'),
            'downloadApi' => url('extraccion-dev-fija/informes-falla/cargar/[numReporte]/download'),
        ];
        return view("extraccion_dev_fija.carga_informe_fallas", compact("config"));
    }

    public function create(Request $request)
    {
        $serviciosAfectados = $request->input("servicio_afectado_id");
        $fechasIni = $request->input("fecha_ini");
        $horasIni = $request->input("hora_ini");
        $fechasFin = $request->input("fecha_fin");
        $horasFin = $request->input("hora_fin");
        $compensaciones = $request->input("compensacion_id");
        
        $departamentos = $request->input("departamento");
        $provincias = $request->input("provincia");
        $distritos = $request->input("distrito");
        $planos = $request->input("plano");

        $detallePlanos = [];
        foreach($departamentos as $index => $row){
            $detallePlanos[] = [
                "departamento" => $departamentos[$index],
                "provincia" => $provincias[$index],
                "distrito" => $distritos[$index],
                "planos" => $planos[$index],
            ];
        }

        $detalleServicios = [];
        foreach($serviciosAfectados as $index => $row){
            $detalleServicios[] = [
                "servicioAfectadoId" => $serviciosAfectados[$index],
                "fechaIni" => $fechasIni[$index],
                "horaIni" => $horasIni[$index],
                "fechaFin" => $fechasFin[$index],
                "horaFin" => $horasFin[$index],
                "compensacionId" => $compensaciones[$index],
            ];
        }
        
        $this->creator->__invoke($request->input("num_reporte"), $request->file("excel"), $detallePlanos, $detalleServicios);
        // $this->creator->__invoke($request->input("num_reporte"), $request->file("excel"), $detallesExtraccion);
        return response()->json([]);
    }

    public function processView()
    {
        $informesFalla = $this->finder->getPendientesProcesar()["data"];
        $config = [
            'title' => 'Procesar Extracción',
            'processGruposUsuarioApi' => url('extraccion-dev-fija/informes-falla/procesar/grupos-usuario'),
            'processApi' => url('extraccion-dev-fija/informes-falla/procesar'),
            'informesFalla' => $informesFalla
        ];
        return view("extraccion_dev_fija.process_extraccion_fija", compact("config"));
    }

    public function processGruposUsuario(Request $request)
    {
        ini_set('max_execution_time', '7200');
        $numReporte = $request->input("num_reporte");
        $ticket = $request->input("ticket");

        $serviciosById = [];
        $serviciosAfectados = $this->finder->getServiciosAfectados();
        foreach($serviciosAfectados as $row){
            $serviciosById[$row->id] = $row;
        }
        
        $input = $this->findReportInputs->__invoke($numReporte, $ticket)->data();
        
        $departamentos = [];
        $provincias = [];
        $distritos = [];
        $planos = [];
        
        foreach($input->planos as $row){
            $planosStr = [];
            foreach($row->planos as $r){
                $planosStr[] = $r->plano;
            }
            $departamentos[] = $row->departamento;
            $provincias[] = $row->provincia;
            $distritos[] = $row->distrito;
            $planos[] = implode(",", $planosStr);
        }

        $dtFechaIni = DateTime::createFromFormat("Y-m-d H:i:s", $input->fecha_ini);
        $dtFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", $input->fecha_fin);

        $grupoUsuarios = $this->process->processAndGetGruposUsuario(
            $departamentos,
            $provincias,
            $distritos,
            $planos,
            $input->ticket,
            $serviciosById[$input->servicio_afectado_id]->label,
            $dtFechaIni->format("Y-m-d"),
            $dtFechaIni->format("H:i:s"),
            $dtFechaFin->format("Y-m-d"),
            $dtFechaFin->format("H:i:s"),
            $input->meses
        )->data();

        // $this->updater->updateStatusToProcesado($input->numero_reporte, $input->servicio_afectado_id);

        // $this->notifyOnProcessed->__invoke($input->numero_reporte, $input->servicio_afectado_id);
        
        return response()->json($grupoUsuarios);
    }

    public function processEnd(Request $request){
        $numReporte = $request->input("num_reporte");
        $ticket = $request->input("ticket");
        $grupoUsuarios = $request->input("grupo_usuarios");
        
        $input = $this->findReportInputs->__invoke($numReporte, $ticket)->data();

        $this->process->processEnd(
            $ticket,
            $grupoUsuarios
        )->data();

        $this->updater->updateStatusToProcesado($input->numero_reporte, $input->servicio_afectado_id);

        $this->notifyOnProcessed->__invoke($input->numero_reporte, $input->servicio_afectado_id);
        
        return response()->json($input);
    }
}
