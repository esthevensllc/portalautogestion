<?php

namespace AMovil\Reports\DetallePlanes\FacturacionDetallada\Services;

use AMovil\Reports\DetallePlanes\FacturacionDetallada\Domain\FacturacionDetalladaRepository;

class FacturacionDetalladaFinder
{
    private $repo;

    public function __construct(FacturacionDetalladaRepository $repo)
    {
        $this->repo = $repo;
    }
    
    public function getTiposInput()
    {
        return $this->repo->getTiposInput();
    }
}
