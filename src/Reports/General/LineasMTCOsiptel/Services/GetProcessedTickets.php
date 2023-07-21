<?php

namespace AMovil\Reports\General\LineasMTCOsiptel\Services;

use AMovil\Reports\General\LineasMTCOsiptel\Domain\LineasMTCOsiptelRepository;

class GetProcessedTickets
{
    private $repo;

    public function __construct(LineasMTCOsiptelRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($tipo_plan, $tipo_solicitud)
    {
        return $this->repo->getTicketProcessed($tipo_plan, $tipo_solicitud);
    }
}
