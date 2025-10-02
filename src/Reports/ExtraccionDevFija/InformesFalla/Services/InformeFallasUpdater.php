<?php

namespace AMovil\Reports\ExtraccionDevFija\InformesFalla\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFallasRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFijaStatus;
use DateTime;

class InformeFallasUpdater
{
    private $repo;
    private $extraccionFijaRepo;
    private $authService;
    private $notifyOnApproved;

    public function __construct(
        InformeFallasRepository $repo,
        ExtraccionDevFijaRepository $extraccionFijaRepo,
        AuthService $authService,
        NotifyUsersOnInformeFallasApproved $notifyOnApproved
    ) {
        $this->repo = $repo;
        $this->extraccionFijaRepo = $extraccionFijaRepo;
        $this->authService = $authService;
        $this->notifyOnApproved = $notifyOnApproved;
    }

    public function updateStatusToRevisado(string $numReporte, $servicioAfectadoId, $userIpAddress) {
        $this->repo->updateStatusToRevisado($numReporte, $servicioAfectadoId);
        $this->repo->registerStatusChanges($numReporte, $servicioAfectadoId, $this->authService->getUserIdentifier(), $userIpAddress, InformeFijaStatus::REVISADO, new DateTime());
    }

    public function updateStatusToAprobado(string $numReporte, $servicioAfectadoId, string $ticket, $userIpAddress) {
        
        $this->repo->updateStatusToAprobado($numReporte, $servicioAfectadoId, $ticket);
        $this->extraccionFijaRepo->updateTicketServicioInputByNumReporte($numReporte, $servicioAfectadoId, $ticket);
        $this->repo->registerStatusChanges($numReporte, $servicioAfectadoId, $this->authService->getUserIdentifier(), $userIpAddress, InformeFijaStatus::APROBADO, new DateTime());
        $this->notifyOnApproved->__invoke($numReporte, $servicioAfectadoId);
    }

    public function updateStatusToDesaprobado(string $numReporte, $servicioAfectadoId, $userIpAddress) {
        $this->repo->updateStatusToDesaprobado($numReporte, $servicioAfectadoId);
        $this->extraccionFijaRepo->updateTicketServicioInputByNumReporte($numReporte, $servicioAfectadoId, null);
        $this->repo->registerStatusChanges($numReporte, $servicioAfectadoId, $this->authService->getUserIdentifier(), $userIpAddress, InformeFijaStatus::DESAPROBADO, new DateTime());
    }

    public function updateStatusToEnEspera(string $numReporte, $servicioAfectadoId, $userIpAddress) {
        $this->repo->updateStatusToEnEspera($numReporte, $servicioAfectadoId);
        $this->extraccionFijaRepo->updateTicketServicioInputByNumReporte($numReporte, $servicioAfectadoId, null);
        $this->repo->registerStatusChanges($numReporte, $servicioAfectadoId, $this->authService->getUserIdentifier(), $userIpAddress, InformeFijaStatus::EN_ESPERA, new DateTime());
    }

    public function updateStatusToEnEjecucion(string $numReporte, $servicioAfectadoId, $userIpAddress) {
        $this->repo->updateStatusToEnEjecucion($numReporte, $servicioAfectadoId);
        $this->repo->registerStatusChanges($numReporte, $servicioAfectadoId, $this->authService->getUserIdentifier(), $userIpAddress, InformeFijaStatus::EN_EJECUCION, new DateTime());
    }

    public function updateStatusToEnEsperaEjecucion(string $numReporte, $servicioAfectadoId, $userIpAddress) {
        $this->repo->updateStatusToEnEsperaEjecucion($numReporte, $servicioAfectadoId);
        $this->repo->registerStatusChanges($numReporte, $servicioAfectadoId, $this->authService->getUserIdentifier(), $userIpAddress, InformeFijaStatus::EN_ESPERA_EJECUCION, new DateTime());
    }

    public function updateStatusToProcesado(string $numReporte, $servicioAfectadoId, $userIpAddress) {
        $this->repo->updateStatusToProcesado($numReporte, $servicioAfectadoId);
        $this->repo->registerStatusChanges($numReporte, $servicioAfectadoId, $this->authService->getUserIdentifier(), $userIpAddress, InformeFijaStatus::PROCESADO, new DateTime());
    }
}
