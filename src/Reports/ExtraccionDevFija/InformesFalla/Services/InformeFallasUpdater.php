<?php

namespace AMovil\Reports\ExtraccionDevFija\InformesFalla\Services;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFallasRepository;

class InformeFallasUpdater
{
    private $repo;
    private $extraccionFijaRepo;
    private $notifyOnApproved;

    public function __construct(
        InformeFallasRepository $repo,
        ExtraccionDevFijaRepository $extraccionFijaRepo,
        NotifyUsersOnInformeFallasApproved $notifyOnApproved
    ) {
        $this->repo = $repo;
        $this->extraccionFijaRepo = $extraccionFijaRepo;
        $this->notifyOnApproved = $notifyOnApproved;
    }

    public function updateStatusToRevisado(string $numReporte, $servicioAfectadoId) {
        $this->repo->updateStatusToRevisado($numReporte, $servicioAfectadoId);
    }

    public function updateStatusToAprobado(string $numReporte, $servicioAfectadoId, string $ticket) {
        
        $this->repo->updateStatusToAprobado($numReporte, $servicioAfectadoId, $ticket);
        $this->extraccionFijaRepo->updateTicketServicioInputByNumReporte($numReporte, $servicioAfectadoId, $ticket);
        $this->notifyOnApproved->__invoke($numReporte, $servicioAfectadoId);
    }

    public function updateStatusToDesaprobado(string $numReporte, $servicioAfectadoId) {
        $this->repo->updateStatusToDesaprobado($numReporte, $servicioAfectadoId);
        $this->extraccionFijaRepo->updateTicketServicioInputByNumReporte($numReporte, $servicioAfectadoId, null);
    }

    public function updateStatusToEnEspera(string $numReporte, $servicioAfectadoId) {
        $this->repo->updateStatusToEnEspera($numReporte, $servicioAfectadoId);
        $this->extraccionFijaRepo->updateTicketServicioInputByNumReporte($numReporte, $servicioAfectadoId, null);
    }

    public function updateStatusToEnEjecucion(string $numReporte, $servicioAfectadoId) {
        $this->repo->updateStatusToEnEjecucion($numReporte, $servicioAfectadoId);
    }

    public function updateStatusToEnEsperaEjecucion(string $numReporte, $servicioAfectadoId) {
        $this->repo->updateStatusToEnEsperaEjecucion($numReporte, $servicioAfectadoId);
    }

    public function updateStatusToProcesado(string $numReporte, $servicioAfectadoId) {
        $this->repo->updateStatusToProcesado($numReporte, $servicioAfectadoId);
    }
}
