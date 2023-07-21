<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;

class FindReportInput
{
    private $repo;
    
    public function __construct(ExtraccionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($numero)
    {
        $data = $this->repo->findInputFor($numero);
        return [
            'data' => $data
        ];
    }
}
