<?php

namespace AMovil\Reports\DAPU\Adquisiciones\Services;

use AMovil\Reports\DAPU\Adquisiciones\Domain\AdquisicionRepository;
use AMovil\Shared\Application\Response;

class GetAdquisiciones
{
    private $repo;

    public function __construct(AdquisicionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($imei): Response
    {
        $data = $this->repo->getByImei($imei);
        return new Response([], $data);
    }
}
