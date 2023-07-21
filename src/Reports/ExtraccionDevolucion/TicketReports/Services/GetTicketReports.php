<?php

namespace AMovil\Reports\ExtraccionDevolucion\TicketReports\Services;

use AMovil\Reports\ExtraccionDevolucion\TicketReports\Domain\TicketReportRepository;

class GetTicketReports
{
    private $repo;
    public function __construct(TicketReportRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getTickets()
    {
        return $this->repo->getTickets();
    }

    public function getDepartamentos()
    {
        return $this->repo->getDepartamentos();
    }
}
