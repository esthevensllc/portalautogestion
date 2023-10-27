<?php

namespace AMovil\Reports\ExtraccionDevFija\InformesFalla\Services;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFallasRepository;
use AMovil\Reports\ExtraccionDevFija\TicketReports\Domain\FijaTicketReportRepository;
use Exception;

class DeleteInformeFallas
{
    private $repo;
    private $extraccionFijaRepo;
    private $ticketRepo;

    public function __construct(
        InformeFallasRepository $repo,
        ExtraccionDevFijaRepository $extraccionFijaRepo,
        FijaTicketReportRepository $ticketRepo
    ) {
        $this->repo = $repo;
        $this->extraccionFijaRepo = $extraccionFijaRepo;
        $this->ticketRepo = $ticketRepo;
    }

    public function __invoke(string $numReporte, $servicioAfectadoId)
    {
        $informesFalla = $this->repo->getByCriteria([
            "numero_reporte.eq.{$numReporte}",
            "servicio_afectado_id.eq.{$servicioAfectadoId}"
        ]);
        if(count($informesFalla["data"]) === 0){
            throw new Exception("No se encontro el informe de fallas");
        }
        $ticket = $informesFalla["data"][0]["ticket"];
        $this->ticketRepo->deleteBy($ticket);
        $this->repo->delete($numReporte, $servicioAfectadoId);
        $this->extraccionFijaRepo->deleteServicioInput($numReporte, $servicioAfectadoId);

        $informesFalla = $this->repo->getByCriteria(["numero_reporte.eq.{$numReporte}"])["data"];
        if(count($informesFalla) === 0){
            $this->extraccionFijaRepo->deletePlanoInput($numReporte);
        }
    }
}
