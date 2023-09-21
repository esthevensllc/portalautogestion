<?php

namespace AMovil\Reports\General\BloqueoImei\Services;

use AMovil\Reports\General\BloqueoImei\Domain\BloqueoImeiRepository;

class SaveImeiCDRConfig
{
    private $repo;

    public function __construct(BloqueoImeiRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($generateReport)
    {
        $this->repo->saveConfig((int) $generateReport === 1);
    }
}
