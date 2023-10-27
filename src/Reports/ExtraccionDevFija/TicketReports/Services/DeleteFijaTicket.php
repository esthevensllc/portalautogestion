<?php

namespace AMovil\Reports\ExtraccionDevFija\TicketReports\Services;

use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFallasRepository;
use AMovil\Reports\ExtraccionDevFija\TicketReports\Domain\FijaTicketReportRepository;
use Exception;

class DeleteFijaTicket
{
    private $repo;
    private $informeFallasRepo;

    public function __construct(
        FijaTicketReportRepository $repo,
        InformeFallasRepository $informeFallasRepo
    ) {
        $this->repo = $repo;
        $this->informeFallasRepo = $informeFallasRepo;
    }

    public function __invoke($ticket)
    {
        $response = $this->informeFallasRepo->getByCriteria(["ticket.eq.{$ticket}"]);
        if(count($response["data"]) === 0){
            throw new Exception("No se encontro un informe de fallas con un ticket '{$ticket}' asignado");
        }
        $numReporte = $response["data"][0]["numero_reporte"];
        $servicioAfectadoId = $response["data"][0]["servicio_afectado_id"];

        $this->repo->deleteBy($ticket);
        $this->informeFallasRepo->updateStatusToSinProcesar($numReporte, $servicioAfectadoId);
    }
}
