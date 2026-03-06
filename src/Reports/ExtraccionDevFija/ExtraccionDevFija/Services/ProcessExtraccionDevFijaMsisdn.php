<?php

namespace AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFallasRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFijaTipoReporte;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Services\CreateInformeFallas;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Services\InformeFallasFinder;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Services\InformeFallasUpdater;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Services\NotifyUsersOnInformeFallasProcessed;
use AMovil\Reports\ExtraccionDevolucion\TablaInteres\Domain\TablaInteresRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use DateTime;
use Exception;

class ProcessExtraccionDevFijaMsisdn
{
    private $informeFallasrepo;
    private $extraccionFijaRepo;
    private $creator;
    private $finder;
    private $findReportInputs;
    private $extraccion;
    private $updater;
    private $tablaInteresRepo;
    private $notifyOnProcessed;
    private $sendFilePrepagoEvent;

    public function __construct(
        InformeFallasRepository $informeFallasrepo,
        ExtraccionDevFijaRepository $extraccionFijaRepo,
        CreateInformeFallas $creator,
        InformeFallasFinder $finder,
        FindReportInputs $findReportInputs,
        ProcessExtraccionDevFija $extraccion,
        InformeFallasUpdater $updater,
        TablaInteresRepository $tablaInteresRepo,
        NotifyUsersOnInformeFallasProcessed $notifyOnProcessed,
        SendFilePrepagoProcesadoEvent $sendFilePrepagoEvent
    ){
        $this->informeFallasrepo = $informeFallasrepo;
        $this->extraccionFijaRepo = $extraccionFijaRepo;
        $this->creator = $creator;
        $this->finder = $finder;
        $this->findReportInputs = $findReportInputs;
        $this->extraccion = $extraccion;
        $this->updater = $updater;
        $this->tablaInteresRepo = $tablaInteresRepo;
        $this->notifyOnProcessed = $notifyOnProcessed;
        $this->sendFilePrepagoEvent = $sendFilePrepagoEvent;
    }

    // public function __invoke($numReporte, $excel, $ticket, $servicioAfectadoId, $fechasIni, $horasIni, $fechasFin, $horasFin, $compensacionId)
    public function __invoke($numReporte, FileInput $excel, FileInput $clientesFile, $detalleServicios, $filterFlag, $userIpAddress)
    {
        $validationTablaInteres = $this->validateTablaInteres();
        if ($validationTablaInteres !== null) {
            return $validationTablaInteres;
        }

        $result = $this->finder->__invoke(["numero_reporte.eq.{$numReporte}"]);
        if(count($result["data"]) > 0){
            return Response::respError(["message" => "El informe de fallas '{$numReporte}' ya existe"]);
        }

        $this->validateTickets($detalleServicios);

        $tickets = [];

        foreach ($detalleServicios as $index => $row) {
            $ticket = $row['ticket'];
            $result = $this->finder->__invoke(["ticket.eq.{$ticket}"]);
            if(count($result["data"]) > 0){
                return Response::respError(["message" => "El ticket '{$ticket}' ya existe"]);
            }
            $detalleServicios[$index]['ticket'] = $ticket;
            $tickets[] = $ticket;
        }

        $this->createInformeFallas($numReporte, $excel, $clientesFile, $detalleServicios);

        foreach ($detalleServicios as $row) {
            $servicioAfectadoId = $row['servicioAfectadoId'];
            $ticket = $row['ticket'];
            $this->informeFallasrepo->updateStatusToRevisado($numReporte, $servicioAfectadoId);
            $this->informeFallasrepo->updateStatusToAprobado($numReporte, $servicioAfectadoId, $ticket);
            $this->extraccionFijaRepo->updateTicketServicioInputByNumReporte($numReporte, $servicioAfectadoId, $ticket);

            $this->process($numReporte, $ticket, $filterFlag, $userIpAddress);
        }

        return Response::respData(["tickets" => $tickets]);
    }

    private function validateTablaInteres(){
        $dtLastDay = new DateTime();
        $dtLastDay->modify("-1 day");
        if (!$this->tablaInteresRepo->existsIn($dtLastDay)) {
            return Response::respError(["message" => "La tabla de interes no esta actualizada"]);
        }
        return null;
    }

    private function validateTickets(array $detalles)
    {
        $ticketsByKey = [];
        foreach($detalles as $row){
            $key = $row["ticket"];
            if(array_key_exists($key, $ticketsByKey)){
                throw new Exception("No se puede agregar el mismo ticket mas de una vez");
            }
            $ticketsByKey[$key] = $row;
        }
    }

    private function createInformeFallas($numReporte, $excel, $clientesFile, $detalleServicios){
        // $serviciosAfectados = $request->input("servicio_afectado_id");
        // $fechasIni = $request->input("fecha_ini");
        // $horasIni = $request->input("hora_ini");
        // $fechasFin = $request->input("fecha_fin");
        // $horasFin = $request->input("hora_fin");
        // $compensaciones = $request->input("compensacion_id");

        $detallePlanos = [[
            "departamento" => "-",
            "provincia" => "-",
            "distrito" => "-",
            "planos" => "0",
        ]];

        // $detalleServicios = [[
        //     "servicioAfectadoId" => $serviciosAfectadoId,
        //     "fechaIni" => $fechasIni,
        //     "horaIni" => $horasIni,
        //     "fechaFin" => $fechasFin,
        //     "horaFin" => $horasFin,
        //     "compensacionId" => $compensacionId,
        // ]];
        
        $this->creator->__invoke($numReporte, InformeFijaTipoReporte::BY_CODCLI, $excel, $clientesFile, $detallePlanos, $detalleServicios);
    }

    private function process($numReporte, $ticket, $filterFlag, $userIpAddress){
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

        $this->extraccion->processAndGetGruposUsuario(
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
        );

        $this->extraccion->processEnd(
            $input->ticket,
            1,
            $filterFlag
        )->data();

        $this->updater->updateStatusToProcesado($input->numero_reporte, $input->servicio_afectado_id, $userIpAddress);

        $this->notifyOnProcessed->__invoke($input->numero_reporte, $input->servicio_afectado_id);
        $this->sendFilePrepagoEvent->__invoke($input->ticket);
    }
}
