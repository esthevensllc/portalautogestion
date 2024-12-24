<?php

namespace AMovil\Reports\ReporteLineasEnrutadas\Services;

use AMovil\Reports\ReporteLineasEnrutadas\Domain\ReporteLineasEnrutadasRepository;

class ReporteLineasEnrutadasFinder
{
    private $repo;

    public function __construct(ReporteLineasEnrutadasRepository $repo)
    {
        $this->repo = $repo;
    }

}
