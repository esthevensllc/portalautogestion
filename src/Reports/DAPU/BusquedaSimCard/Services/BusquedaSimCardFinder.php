<?php

namespace AMovil\Reports\DAPU\BusquedaSimCard\Services;

use AMovil\Reports\DAPU\BusquedaSimCard\Domain\BusquedaSimCardRepository;
use AMovil\Shared\Application\Response;

class BusquedaSimCardFinder
{
private $repo;

    public function __construct(BusquedaSimCardRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke(string $iccid)
    {
        $data = $this->repo->getByIccid($iccid);
        return new Response([], $data);
    }
}
