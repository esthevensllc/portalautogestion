<?php

namespace AMovil\Reports\DAPU\CambioTitularidad\Services;

use AMovil\Reports\DAPU\CambioTitularidad\Domain\CambioTitularidadRepository;
use AMovil\Shared\Application\Response;

class CambioTitularidadFinder
{
    private $repo;

    public function __construct(CambioTitularidadRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke(string $linea)
    {
        $data = $this->repo->getByLinea($linea);
        return new Response([], $data);
    }
}
