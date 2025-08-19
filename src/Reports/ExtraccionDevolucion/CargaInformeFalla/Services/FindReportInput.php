<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use Exception;

class FindReportInput
{
    private $repo;
    private $storage;
    private $baseStoragePath = "/space/reportes/INFORME_FALLAS_NOC";
    
    public function __construct(ExtraccionRepository $repo, StorageService $storageService)
    {
        $this->repo = $repo;
        $this->storage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
    }

    public function __invoke($numero)
    {
        $data = $this->repo->findInputFor($numero);
        return [
            'data' => $data
        ];
    }

    public function downloadInformeFalla($numReporte)
    {
        $data = $this->repo->findInputFor($numReporte);
        if(count($data) === 0){
            return Response::respError(["messge" => "El informe de fallas no existe"]);
        }
        $informe = $data[0];
        $content = $this->storage->get("{$this->baseStoragePath}/{$numReporte}_{$informe->name_file}");
        return Response::respData([
            "filename" => $informe->name_file,
            "content" => $content
        ]);
    }
}
