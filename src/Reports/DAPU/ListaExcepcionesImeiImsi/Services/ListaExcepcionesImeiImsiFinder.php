<?php

namespace AMovil\Reports\DAPU\ListaExcepcionesImeiImsi\Services;

use AMovil\Reports\DAPU\ListaExcepcionesImeiImsi\Domain\ListaExcepcionesImeiImsiRepository;
use AMovil\Shared\Application\Response;

class ListaExcepcionesImeiImsiFinder
{
    private $repo;

    public function __construct(ListaExcepcionesImeiImsiRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($imei)
    {    
        $data = $this->repo->getByImei($imei);
        return new Response([], $data);
    }
}
