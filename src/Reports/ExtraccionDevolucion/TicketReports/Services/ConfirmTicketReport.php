<?php

namespace AMovil\Reports\ExtraccionDevolucion\TicketReports\Services;

use AMovil\Reports\ExtraccionDevolucion\TicketReports\Domain\TicketReportRepository;
use Exception;

class ConfirmTicketReport
{
    private $repo;
    public function __construct(TicketReportRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($ticket, $departamento)
    {
        if(!$this->repo->reportIsConfirmed($ticket, $departamento)){
            $this->repo->confirmBy($ticket, $departamento);
        }else{
            throw new Exception("El ticket ya se confirmo y no se puede eliminar");
        }
    }
}
