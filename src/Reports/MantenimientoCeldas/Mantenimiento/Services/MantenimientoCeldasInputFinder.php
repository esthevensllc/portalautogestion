<?php

namespace AMovil\Reports\MantenimientoCeldas\Mantenimiento\Services;

use AMovil\Reports\MantenimientoCeldas\Mantenimiento\Domain\MantenimientoCeldaRepository;

class MantenimientoCeldasInputFinder
{
    private $repo;

    public function __construct(MantenimientoCeldaRepository $repo)
    {
        $this->repo = $repo;    
    }

    public function __invoke($ticket, $departamento)
    {
        return $this->repo->getInputs($ticket, $departamento);
    }
}
