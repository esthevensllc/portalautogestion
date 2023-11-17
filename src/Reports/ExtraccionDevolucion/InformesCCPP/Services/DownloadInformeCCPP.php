<?php

namespace AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Services;

use AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Domain\InformeCCPPRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use Exception;

class DownloadInformeCCPP
{
    private $repo;
    private $localStorage;
    private $baseStoragePath = "/space/reportes/informes_ccpp";

    public function __construct(
        InformeCCPPRepository $repo,
        StorageService $storageService
    ) {
        $this->repo = $repo;
        $this->localStorage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
    }

    public function __invoke($id){
        $criteriaResponse = $this->repo->getByCriteria(["id.eq.{$id}"]);
        if (count($criteriaResponse["data"]) === 0) {
            throw new Exception("El archivo no existe");
        }
        $filename = $criteriaResponse["data"][0]->filename;
        $content = $this->localStorage->get("{$this->baseStoragePath}/{$filename}");
        return Response::respData([
            "filename" => $filename,
            "type" => "xlsx",
            "content" => $content
        ]);
    }
}
