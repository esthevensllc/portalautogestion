<?php

namespace AMovil\Reports\DAPU\ListaEir\Services;

use AMovil\Reports\DAPU\ListaEir\Domain\ListaEirRepository;
use AMovil\Shared\Application\Response;

class ListaEirFinder
{
    private $repo;

    public function __construct(ListaEirRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($imei)
    {    
        $data = $this->repo->getByImei($imei);
        return new Response([], $data);
    }
}
