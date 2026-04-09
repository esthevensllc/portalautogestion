<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\InformeStatus;
use DateTime;

class FaseFinalReport
{
    private $repo;
    private $authService;
    
    public function __construct(ExtraccionRepository $repo, AuthService $authService)
    {
        $this->repo = $repo;
        $this->authService = $authService;
    }

    public function __invoke($numero_reporte, $estado, $userIpAddress)
    {
        $data = $this->repo->saveFase($numero_reporte, $estado);
        $this->repo->registerStatusChanges($numero_reporte, null, $this->authService->getUserIdentifier(), $userIpAddress, InformeStatus::FASE_FINAL, new DateTime());

        return $data;
    }
}
