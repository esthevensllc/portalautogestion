<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;

class GetReports
{
    private $repo;
    
    public function __construct(ExtraccionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke()
    {
        $data = $this->repo->getReportes();

        return [
            'data' => $data
        ];
    }
}
