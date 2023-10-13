<?php

namespace AMovil\Reports\GeoMarketing\Services;

use AMovil\Reports\GeoMarketing\Domain\GeoMarketingRepository;

class GeoMarketingFinder
{
    private $repo;
    public function __construct(GeoMarketingRepository $repo)
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
