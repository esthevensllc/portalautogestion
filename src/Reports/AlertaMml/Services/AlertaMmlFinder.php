<?php

namespace AMovil\Reports\AlertaMml\Services;

use AMovil\Reports\AlertaMml\Domain\AlertaMmlRepository;

class AlertaMmlFinder
{
    private $repo;
    public function __construct(AlertaMmlRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getLogs()
    {
        return $this->repo->getLogs();
    }

    public function getBlackAndWhiteListSummary()
    {
        return $this->repo->getBlackAndWhiteListSummary();
    }
}
