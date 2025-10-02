<?php

namespace AMovil\Reports\ExtraccionDevFija\TicketReports\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFallasRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFijaStatus;
use AMovil\Reports\ExtraccionDevFija\TicketReports\Domain\FijaTicketReportRepository;
use DateTime;
use Exception;

class DeleteFijaTicket
{
    private $repo;
    private $informeFallasRepo;
    private $authService;

    public function __construct(
        FijaTicketReportRepository $repo,
        InformeFallasRepository $informeFallasRepo,
        AuthService $authService
    ) {
        $this->repo = $repo;
        $this->informeFallasRepo = $informeFallasRepo;
        $this->authService = $authService;
    }

    public function __invoke($ticket, $userIpAddress)
    {
        $response = $this->informeFallasRepo->getByCriteria(["ticket.eq.{$ticket}"]);
        if(count($response["data"]) === 0){
            throw new Exception("No se encontro un informe de fallas con un ticket '{$ticket}' asignado");
        }
        $numReporte = $response["data"][0]->numero_reporte;
        $servicioAfectadoId = $response["data"][0]->servicio_afectado_id;

        $this->repo->deleteBy($ticket);
        $this->informeFallasRepo->updateStatusToSinProcesar($numReporte, $servicioAfectadoId);
        $this->informeFallasRepo->registerStatusChanges($numReporte, $servicioAfectadoId, $this->authService->getUserIdentifier(), $userIpAddress, InformeFijaStatus::SIN_PROCESAR, new DateTime());
    }
}
