<?php

namespace AMovil\Reports\ClientesPlanos\Services;

use AMovil\Reports\ClientesPlanos\Domain\ClientesPlanosRepository;
use AMovil\Auth\AccessControl\Domain\AuthService;
use DateTime;

class ClientesPlanosFinder
{
    private $repo;
    private $authService;

    public function __construct(ClientesPlanosRepository $repo, AuthService $authService)
    {
        $this->repo = $repo;
        $this->authService = $authService;
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

    public function get(){
        $this->userIdentifier = $this->authService->getUserIdentifier();
        return $this->repo->getByCriteria(["codigo_c.eq.".$this->userIdentifier,"name.eq.CLIENTES_PLANOS"])["data"];
    }
}
