<?php

namespace AMovil\Reports\General\BloqueoImei\Services;

use AMovil\Reports\General\BloqueoImei\Domain\BloqueoImeiRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use DateTime;
use Exception;

class ExportImeiCDRReport
{
    private $repo;
    private $storage;
    private $baseStoragePath = "/space/reportes/imei_cdr_automatico";

    public function __construct(BloqueoImeiRepository $repo, StorageService $storageService)
    {
        $this->repo = $repo;
        $this->storage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
    }

    public function __invoke($id): Response
    {
        $reports = $this->repo->getAutomaticReportByCriteria([["id", $id]]);
        if(count($reports) === 0){
            throw new Exception("El reporte no existe");
        }
        $report = $reports[0];
        $subDirectory = DateTime::createFromFormat("Y-m-d H:i:s", $report["fecha"])->format("Ym");
        $filename = $report["filename"];
        $content = $this->storage->get("{$this->baseStoragePath}/{$subDirectory}/{$filename}");

        return new Response([], [
            "filename" => $filename,
            "type" => "xlsx",
            "content" => $content
        ]);
    }

    public function __invokeOnline($id): Response
    {
        $reports = $this->repo->getAutomaticOnlineReportByCriteria([["id", $id]]);
        if(count($reports) === 0){
            throw new Exception("El reporte no existe");
        }
        $report = $reports[0];
        $subDirectory = DateTime::createFromFormat("Y-m-d H:i:s", $report["fecha"])->format("Ym");
        $filename = $report["filename"];
        $content = $this->storage->get("{$this->baseStoragePath}/{$subDirectory}/{$filename}");

        return new Response([], [
            "filename" => $filename,
            "type" => "xlsx",
            "content" => $content
        ]);
    }
}
