<?php

namespace AMovil\Reports\MantenimientoCeldas\TicketReports\Services;

use AMovil\Reports\MantenimientoCeldas\TicketReports\Domain\TicketReportRepository;

class TicketReportFinder
{
    private $repo;

    public function __construct(TicketReportRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke()
    {
        return $this->repo->getReports();
    }
}
