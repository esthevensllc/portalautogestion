<?php

namespace AMovil\Reports\FacturaDetallada\FacturacionDetallada\Services;

use AMovil\Reports\FacturaDetallada\FacturacionDetallada\Domain\FacturacionDetalladaRepository;

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
