<?php

namespace AMovil\Reports\DAPU\Lineas\Services;

use AMovil\Reports\DAPU\Lineas\Domain\LineaRepository;
use AMovil\Shared\Application\Response;

class GetDataLinea
{
    private $repo;

    public function __construct(LineaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($linea): Response
    {
        $data = $this->repo->getUsuariosByLinea($linea);
        return new Response([], $data);
    }
}
