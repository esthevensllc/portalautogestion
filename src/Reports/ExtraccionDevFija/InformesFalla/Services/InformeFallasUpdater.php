<?php

namespace AMovil\Reports\ExtraccionDevFija\InformesFalla\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFallasRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFijaStatus;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFijaTipoReporte;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use DateTime;
use Exception;
use InvalidArgumentException;

class InformeFallasUpdater
{
    private $repo;
    private $extraccionFijaRepo;
    private $authService;
    private $notifyOnApproved;
    private $baseStoragePath = "/space/reportes/informes_falla_fija";
    private $storage;

    public function __construct(
        InformeFallasRepository $repo,
        ExtraccionDevFijaRepository $extraccionFijaRepo,
        AuthService $authService,
        NotifyUsersOnInformeFallasApproved $notifyOnApproved,
        StorageService $storageService,
    ) {
        $this->repo = $repo;
        $this->extraccionFijaRepo = $extraccionFijaRepo;
        $this->authService = $authService;
        $this->notifyOnApproved = $notifyOnApproved;
        $this->storage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
    }

    public function updateStatusToRevisado(string $numReporte, $servicioAfectadoId, $userIpAddress) {
        $this->repo->updateStatusToRevisado($numReporte, $servicioAfectadoId);
        $this->repo->registerStatusChanges($numReporte, $servicioAfectadoId, $this->authService->getUserIdentifier(), $userIpAddress, InformeFijaStatus::REVISADO, new DateTime());
    }

    public function updateStatusToAprobado(string $numReporte, $servicioAfectadoId, string $ticket, $userIpAddress) {
        
        $this->repo->updateStatusToAprobado($numReporte, $servicioAfectadoId, $ticket);
        $this->extraccionFijaRepo->updateTicketServicioInputByNumReporte($numReporte, $servicioAfectadoId, $ticket);
        $this->repo->registerStatusChanges($numReporte, $servicioAfectadoId, $this->authService->getUserIdentifier(), $userIpAddress, InformeFijaStatus::APROBADO, new DateTime());
        $this->notifyOnApproved->__invoke($numReporte, $servicioAfectadoId);
    }

    public function updateStatusToDesaprobado(string $numReporte, $servicioAfectadoId, $userIpAddress) {
        $this->repo->updateStatusToDesaprobado($numReporte, $servicioAfectadoId);
        $this->extraccionFijaRepo->updateTicketServicioInputByNumReporte($numReporte, $servicioAfectadoId, null);
        $this->repo->registerStatusChanges($numReporte, $servicioAfectadoId, $this->authService->getUserIdentifier(), $userIpAddress, InformeFijaStatus::DESAPROBADO, new DateTime());
    }

    public function updateStatusToEnEspera(string $numReporte, $servicioAfectadoId, $userIpAddress) {
        $this->repo->updateStatusToEnEspera($numReporte, $servicioAfectadoId);
        $this->extraccionFijaRepo->updateTicketServicioInputByNumReporte($numReporte, $servicioAfectadoId, null);
        $this->repo->registerStatusChanges($numReporte, $servicioAfectadoId, $this->authService->getUserIdentifier(), $userIpAddress, InformeFijaStatus::EN_ESPERA, new DateTime());
    }

    public function updateStatusToEnEjecucion(string $numReporte, $servicioAfectadoId, $userIpAddress) {
        $this->repo->updateStatusToEnEjecucion($numReporte, $servicioAfectadoId);
        $this->repo->registerStatusChanges($numReporte, $servicioAfectadoId, $this->authService->getUserIdentifier(), $userIpAddress, InformeFijaStatus::EN_EJECUCION, new DateTime());
    }

    public function updateStatusToEnEsperaEjecucion(string $numReporte, $servicioAfectadoId, $userIpAddress) {
        $this->repo->updateStatusToEnEsperaEjecucion($numReporte, $servicioAfectadoId);
        $this->repo->registerStatusChanges($numReporte, $servicioAfectadoId, $this->authService->getUserIdentifier(), $userIpAddress, InformeFijaStatus::EN_ESPERA_EJECUCION, new DateTime());
    }

    public function updateStatusToProcesado(string $numReporte, $servicioAfectadoId, $userIpAddress) {
        $this->repo->updateStatusToProcesado($numReporte, $servicioAfectadoId);
        $this->repo->registerStatusChanges($numReporte, $servicioAfectadoId, $this->authService->getUserIdentifier(), $userIpAddress, InformeFijaStatus::PROCESADO, new DateTime());
    }

    public function updateTicketReporteProcesado(string $numReporte, $servicioAfectadoId, $ticket) {
        $filters = ["numero_reporte.eq.{$numReporte}", "servicio_afectado_id.eq.{$servicioAfectadoId}", "tipo_reporte.eq.".InformeFijaTipoReporte::BY_CODCLI];
        $result = $this->repo->getByCriteria($filters);
        if(count($result["data"]) < 1){
            throw new Exception("El informe de fallas no existe");
        }
        $ticketAnterior = $result["data"][0]->ticket;
        $this->repo->updateTicketToReporteProcesado($numReporte, $servicioAfectadoId, $ticketAnterior, $ticket);
    }

    public function updateNombreArchivo(string $numReporte, FileInput $file){
        $resultCriteria = $this->repo->getByCriteria(["numero_reporte.eq.{$numReporte}"]);
        if (count($resultCriteria['data']) === 0) {
            throw new Exception("El informe de fallas no existe");
        }

        $informeFalla = $resultCriteria['data'][0];

        if ((int) $informeFalla->tipo_reporte !== InformeFijaTipoReporte::BY_CODCLI) {
            throw new InvalidArgumentException("Solo se puede actualizar el nombre de archivo para informes de falla de mantenimiento");
        }
        if ($informeFalla->name_file !== null) {
            $this->storage->delete("{$this->baseStoragePath}/{$informeFalla->name_file}");
        }

        $filename = $file->getFilename();
        $filepath = $file->getFilePath();
        $this->storage->put("{$this->baseStoragePath}/{$filename}", file_get_contents($filepath));
        $this->repo->updateNombreArchivo($numReporte, $filename);
    }
}
