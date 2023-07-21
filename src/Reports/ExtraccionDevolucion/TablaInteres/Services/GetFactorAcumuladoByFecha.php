<?php

namespace AMovil\Reports\ExtraccionDevolucion\TablaInteres\Services;

use AMovil\Reports\ExtraccionDevolucion\TablaInteres\Domain\TablaInteresRepository;
use AMovil\Shared\Application\Response;

class GetFactorAcumuladoByFecha
{
    private $repo;
    
    public function __construct(TablaInteresRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke()
    {
        $data = $this->repo->getFactorAcumuladoByFecha(10);
        return new Response([], $data);
    }

    public function getMaxFechaInteres(){
        return $this->repo->getMaxFechaInteres();
    }
}
