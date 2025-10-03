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

    public function __invoke(int $tipoInput, $value)
    {
        $data = [];
        if ($tipoInput === 1) {
            $data = $this->repo->getByLinea($value);
        } else if ($tipoInput === 2) {
            $data = $this->repo->getByImei($value);
        }
        return new Response([], $data);
    }
}
