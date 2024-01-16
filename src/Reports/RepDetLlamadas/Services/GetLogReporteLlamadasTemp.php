<?php

namespace AMovil\Reports\RepDetLlamadas\Services;

use AMovil\Reports\RepDetLlamadas\Domain\DetalleLlamadasRepository;

class GetLogReporteLlamadasTemp
{
    private $repo;

    public function __construct(DetalleLlamadasRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke()
    {
        return $this->repo->getLogReporteTemp();
    }
}
