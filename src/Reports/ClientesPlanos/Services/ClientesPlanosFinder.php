<?php

namespace AMovil\Reports\ClientesPlanos\Services;

use AMovil\Reports\ClientesPlanos\Domain\ClientesPlanosRepository;
use DateTime;

class ClientesPlanosFinder
{
    private $repo;

    public function __construct(ClientesPlanosRepository $repo)
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
