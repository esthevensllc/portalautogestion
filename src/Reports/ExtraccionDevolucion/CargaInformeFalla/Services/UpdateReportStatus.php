<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\InformeStatus;
use DateTime;

class UpdateReportStatus
{
    private $repo;
    private $authService;
    
    public function __construct(ExtraccionRepository $repo, AuthService $authService)
    {
        $this->repo = $repo;
        $this->authService = $authService;
    }

    public function __invoke($status, $id, $userIpAddress)
    {
        switch ($status) {
            case 'enEjecucionPre':
                $this->repo->enEjecucionPre($id);
                $this->repo->registerStatusChanges($id, null, $this->authService->getUserIdentifier(), $userIpAddress, InformeStatus::EN_EJECUCION_PRE, new DateTime());
                break;
            case 'enEsperaPre':
                $this->repo->enEsperaPre($id);
                $this->repo->registerStatusChanges($id, null, $this->authService->getUserIdentifier(), $userIpAddress, InformeStatus::EN_ESPERA_PRE, new DateTime());
                break;
            case 'enEjecucionPost':
                $this->repo->enEjecucionPost($id);
                $this->repo->registerStatusChanges($id, null, $this->authService->getUserIdentifier(), $userIpAddress, InformeStatus::EN_EJECUCION_POST, new DateTime());
                break;
            case 'enEsperaPost':
                $this->repo->enEsperaPost($id);
                $this->repo->registerStatusChanges($id, null, $this->authService->getUserIdentifier(), $userIpAddress, InformeStatus::EN_ESPERA_POST, new DateTime());
                break;
            default:
                break;
        }
    }
}
