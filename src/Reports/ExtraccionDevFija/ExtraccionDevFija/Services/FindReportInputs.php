<?php

namespace AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaRepository;
use AMovil\Reports\ExtraccionDevFija\TicketReports\Domain\FijaTicketReportRepository;
use AMovil\Shared\Application\Response;

class FindReportInputs
{
    private $repo;
    private $ticketReportRepo;

    public function __construct(
        ExtraccionDevFijaRepository $repo,
        FijaTicketReportRepository $ticketReportRepo
    ) {
        $this->repo = $repo;
        $this->ticketReportRepo = $ticketReportRepo;
    }

    public function __invoke($numReporte, $ticket): Response
    {
        $input = $this->repo->findInputsByNumReporteAndTicket($numReporte, $ticket);
        return Response::respData($input);
    }

    public function getInputsPendientes(string $numReporte){

    }
}
