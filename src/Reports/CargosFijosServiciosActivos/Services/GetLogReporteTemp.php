<?php

namespace AMovil\Reports\CargosFijosServiciosActivos\Services;

use AMovil\Reports\CargosFijosServiciosActivos\Domain\DetalleRepository;
use AMovil\Auth\AccessControl\Domain\AuthService;
use DateTime;

class GetLogReporteTemp
{
    private $repo;
    private $authService;

    public function __construct(DetalleRepository $repo, AuthService $authService)
    {
        $this->repo = $repo;
        $this->authService = $authService;
    }

    public function __invoke()
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        return $this->repo->getByCriteria(["codigo_c.eq.".$this->userIdentifier,"name.eq.CARGOS FIJOS Y SERVICIOS ACTIVOS"])["data"];
    }
}
