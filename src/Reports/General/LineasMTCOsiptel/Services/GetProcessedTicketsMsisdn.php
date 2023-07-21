<?php

namespace AMovil\Reports\General\LineasMTCOsiptel\Services;

use AMovil\Reports\General\LineasMTCOsiptel\Domain\LineasMTCOsiptelRepository;

class GetProcessedTicketsMsisdn
{
    private $repo;

    public function __construct(LineasMTCOsiptelRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($tipo_plan)
    {
        return $this->repo->getTicketProcessedMsisdn($tipo_plan);
    }
}
