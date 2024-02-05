<?php

namespace AMovil\Reports\MantenimientoCeldas\TicketReports\Services;

use AMovil\Reports\MantenimientoCeldas\Mantenimiento\Domain\MantenimientoCeldaRepository;
use AMovil\Reports\MantenimientoCeldas\TicketReports\Domain\TicketReportRepository;

class DeleteTicketReport
{
    private $repo;
    private $mantenimientoRepo;

    public function __construct(TicketReportRepository $repo, MantenimientoCeldaRepository $mantenimientoRepo)
    {
        $this->repo = $repo;
        $this->mantenimientoRepo = $mantenimientoRepo;
    }

    public function __invoke($ticket, $departamento)
    {
        $this->repo->delete($ticket, $departamento);
        $this->mantenimientoRepo->delete($ticket, $departamento);
    }
}
