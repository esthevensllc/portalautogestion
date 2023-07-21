<?php

namespace AMovil\Reports\RepCursado\Services;

use AMovil\Reports\RepCursado\Domain\ReporteCursadoRepository;

class GetPeriodosDisponibles
{
    private $repo;

    public function __construct(ReporteCursadoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke()
    {
        return $this->repo->getPeriodosDisponibles();
    }
}
