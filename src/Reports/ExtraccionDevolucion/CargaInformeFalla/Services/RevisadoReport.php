<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\InformeStatus;
use DateTime;

class RevisadoReport
{
    private $repo;
    private $authService;
    
    public function __construct(ExtraccionRepository $repo, AuthService $authService)
    {
        $this->repo = $repo;
        $this->authService = $authService;
    }

    public function __invoke($id, $userIpAddress)
    {
        $data = $this->repo->revisado($id);
        $this->repo->registerStatusChanges($id, null, $this->authService->getUserIdentifier(), $userIpAddress, InformeStatus::REVISADO, new DateTime());

        return $data;
    }
}
