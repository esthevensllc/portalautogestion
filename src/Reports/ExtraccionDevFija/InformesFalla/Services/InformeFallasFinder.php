<?php

namespace AMovil\Reports\ExtraccionDevFija\InformesFalla\Services;

use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFallasRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use Exception;

class InformeFallasFinder
{
    private $repo;
    private $storage;
    private $baseStoragePath = "/space/reportes/informes_falla_fija";

    public function __construct(InformeFallasRepository $repo, StorageService $storageService)
    {
        $this->repo = $repo;
        $this->storage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
    }

    public function __invoke($filters = [])
    {
        return $this->repo->getByCriteria($filters);
    }

    public function getPendientesProcesar($filters = [])
    {
        $filters[] = "revisado.eq.1";
        $filters[] = "aprobado.eq.1";
        $filters[] = "procesado.eq.0";
        return $this->repo->getByCriteria($filters);
    }

    public function getProcesados($filters = [])
    {
        $filters[] = "revisado.eq.1";
        $filters[] = "aprobado.eq.1";
        $filters[] = "procesado.eq.1";
        return $this->repo->getByCriteria($filters);
    }

    public function getServiciosAfectados()
    {
        return $this->repo->getServiciosAfectados();
    }

    public function downloadInformeFalla($numReporte)
    {
        $filters = ["numero_reporte.eq.{$numReporte}"];
        $result = $this->repo->getByCriteria($filters);
        if(count($result["data"]) < 1){
            throw new Exception("El informe de fallas no existe");
        }
        $informe = $result["data"][0];
        $content = $this->storage->get("{$this->baseStoragePath}/{$informe->name_file}");
        return Response::respData([
            "filename" => $informe->name_file,
            "content" => $content
        ]);
    }
}
