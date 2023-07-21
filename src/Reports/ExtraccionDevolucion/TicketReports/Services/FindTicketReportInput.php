<?php

namespace AMovil\Reports\ExtraccionDevolucion\TicketReports\Services;

use AMovil\Reports\ExtraccionDevolucion\TicketReports\Domain\TicketReportRepository;
use AMovil\Shared\Application\Response;

class FindTicketReportInput
{
    private $repo;
    public function __construct(TicketReportRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($ticket, $departamento): Response
    {
        $data = $this->repo->findInputFor($ticket, $departamento);
        return new Response([], $data);
    }
}
