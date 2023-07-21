<?php

namespace AMovil\Reports\ExtraccionDevolucion\TicketReports\Services;

use AMovil\Reports\ExtraccionDevolucion\TicketReports\Domain\TicketReportRepository;
use Exception;

class DeleteTicketReport
{
    private $repo;
    public function __construct(TicketReportRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($ticket, $departamento)
    {
        if(!$this->repo->reportIsConfirmed($ticket, $departamento)){
            $this->repo->deleteBy($ticket, $departamento);
        }else{
            throw new Exception("El ticket ya se confirmo y no se puede eliminar");
        }
    }
}
