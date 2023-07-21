<?php

namespace AMovil\Reports\DAPU\VentasLinea\Services;

use AMovil\Reports\DAPU\VentasLinea\Domain\VentasLineaRepository;
use AMovil\Shared\Application\Response;

class GetVentasLinea
{
    private $repo;

    public function __construct(VentasLineaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($dni, $fono, $periodo)
    {    
        $data = $this->repo->getByDni_Fono_Periodo($dni, $fono, $periodo);
        return new Response([], $data);
    }
}
