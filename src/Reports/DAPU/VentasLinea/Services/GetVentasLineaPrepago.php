<?php

namespace AMovil\Reports\DAPU\VentasLinea\Services;

use AMovil\Reports\DAPU\VentasLinea\Domain\VentasLineaRepository;
use AMovil\Shared\Application\Response;

class GetVentasLineaPrepago
{
    private $repo;

    public function __construct(VentasLineaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($dni, $fono)
    {    
        $data = $this->repo->getPrepagoByDni_Fono_Periodo($dni, $fono);
        return new Response([], $data);
    }
}
