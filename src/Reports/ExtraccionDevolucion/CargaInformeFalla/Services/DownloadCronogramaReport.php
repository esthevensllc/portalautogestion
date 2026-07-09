<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;

class DownloadCronogramaReport
{
    private $repo;
    private $storage;
    private $baseStoragePath;

    public function __construct(ExtraccionRepository $repo, StorageService $storageService)
    {
        $this->repo = $repo;
        $this->storage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
        //$this->baseStoragePath = public_path('reportes/INFORME_FALLAS_CRONOGRAMAS');
        $this->baseStoragePath = storage_path('app/cronogramas_informe_falla');
    }

    public function __invoke($numReporte)
    {
        $data = $this->repo->findInputFor($numReporte);
        if (count($data) === 0) {
            return Response::respError(["message" => "El informe de fallas no existe"]);
        }

        $informe = $data[0];
        if (empty($informe->cronograma_file)) {
            return Response::respError(["message" => "El informe de fallas no tiene cronograma cargado"]);
        }

        $storagePath = $this->buildStoragePath($numReporte, $informe->cronograma_file);
        if (!$this->storage->exists($storagePath)) {
            return Response::respError(["message" => "El archivo de cronograma no existe en el servidor"]);
        }

        return Response::respData([
            "filename" => $informe->cronograma_file,
            "content" => $this->storage->get($storagePath),
        ]);
    }

    private function buildStoragePath($numReporte, $filename)
    {
        return "{$this->baseStoragePath}/{$numReporte}_{$filename}";
    }
}
