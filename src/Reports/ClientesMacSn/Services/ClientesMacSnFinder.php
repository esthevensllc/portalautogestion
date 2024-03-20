<?php

namespace AMovil\Reports\ClientesMacSn\Services;

use AMovil\Reports\ClientesMacSn\Domain\ClientesMacSnRepository;

class ClientesMacSnFinder
{
    private $repo;

    public function __construct(ClientesMacSnRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getSummary()
    {
        return $this->repo->getClientesMacSnSummary();
    }
}
