<?php

namespace AMovil\Reports\DAPU\HistoricoBloqueos\Services;

use AMovil\Reports\DAPU\HistoricoBloqueos\Domain\HistoricoBloqueoRepository;
use AMovil\Shared\Application\Response;

class GetHistoricoBloqueo
{
    private $repo;

    public function __construct(HistoricoBloqueoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($imei)
    {    
        $data = $this->repo->getByImei($imei);
        return new Response([], $data);
    }
}
