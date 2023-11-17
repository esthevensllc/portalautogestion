<?php

namespace AMovil\Reports\OltCmts\Services;

use AMovil\Reports\OltCmts\Domain\OltCmtsRepository;
use DateTime;

class OltCmtsFinder
{
    private $repo;

    public function __construct(OltCmtsRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getReportTypes()
    {
        return $this->repo->getReportTypes();
    }

    public function getOltsValues(DateTime $fecha)
    {
        return $this->repo->getOltsValues($fecha);
    }

    public function getCmtsValues(DateTime $fecha)
    {
        return $this->repo->getCmtsValues($fecha);
    }

    public function getOltListSummary()
    {
        return $this->repo->getOltListSummary();
    }
}
