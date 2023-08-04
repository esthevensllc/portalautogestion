<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;
use AMovil\Shared\Application\Response;

class GetDepartamentosByNumReporte
{
    private $repo;

    public function __construct(ExtraccionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($num_reporte): Response
    {
        $data = $this->repo->getDepartamentosByNumReporte($num_reporte);
        return new Response([], $data);
    }
}
