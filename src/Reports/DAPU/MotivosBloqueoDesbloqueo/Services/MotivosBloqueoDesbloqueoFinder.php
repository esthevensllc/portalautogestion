<?php

namespace AMovil\Reports\DAPU\MotivosBloqueoDesbloqueo\Services;

use AMovil\Reports\DAPU\MotivosBloqueoDesbloqueo\Domain\MotivosBloqueoDesbloqueoRepository;
use AMovil\Shared\Application\Response;

class MotivosBloqueoDesbloqueoFinder
{
    private $repo;

    public function __construct(MotivosBloqueoDesbloqueoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($imei)
    {
        if(strlen($imei) < 14){
            return new Response([], []);
        }
        $data = $this->repo->getByImei($imei);
        return new Response([], $data);
    }
}
