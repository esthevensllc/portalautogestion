<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;
use AMovil\Reports\ExtraccionDevolucion\TicketReports\Domain\TicketReportRepository;
use AMovil\Shared\Application\Response;

class GetDepartamentosByNumReporte
{
    private $repo;
    private $ticketRepo;

    public function __construct(ExtraccionRepository $repo, TicketReportRepository $ticketRepo)
    {
        $this->repo = $repo;
        $this->ticketRepo = $ticketRepo;
    }

    public function __invoke($num_reporte): Response
    {
        $informesFalla = $this->repo->getReportesByCriteria([["numero_de_reporte", $num_reporte]]);
        $ticket = null;
        $result = [];
        $data = $this->repo->getDepartamentosByNumReporte($num_reporte);

        if(count($informesFalla) > 0){
            $ticket = $informesFalla[0]->ticket;
            foreach($data as $row){
                $ticketReport = $this->ticketRepo->findByTicketAndDepartamento($ticket, $row->departamento);
                if($ticketReport === null){
                    $result[] = $row;
                }
            }
        }else{
            $result = $data;
        }

        return new Response([], $result);
    }
}
