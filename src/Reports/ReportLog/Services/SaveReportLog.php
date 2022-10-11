<?php

namespace AMovil\Reports\ReportLog\Services;

use AMovil\Reports\ReportLog\Domain\ReportLogRepository;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use DateTime;

class SaveReportLog
{
    private $repo;
    private $storage;
    private $base_path1 = "/space/reportes";
    private $base_path2 = "/space/reportes";

    public function __construct(ReportLogRepository $repo, StorageService $storage)
    {
        $this->repo = $repo;
        $this->storage = $storage;
        // $this->base_path = "/space/reportes/";
    }

    public function __invoke(array $log_data, ?string $local_file, string $path)
    {
        $file = null;
        if($local_file !== null){
            $file_parts = explode("/", $local_file);
            $file = $file_parts[count($file_parts)-1];
        }

        $this->repo->save(
            $log_data['name'],
            $log_data['direccion'] ?? null,
            $log_data['area'] ?? null,
            $log_data['contacto'] ?? null,
            $log_data['responsable'] ?? null,
            $file,
            DateTime::createFromFormat("Y-m-d H:i:s", $log_data['ini']),
            DateTime::createFromFormat("Y-m-d H:i:s", $log_data['fin']),
            0,
            $local_file !== null ? 1 : 0,
            $log_data['mensaje'] ?? null
        );

        if($local_file !== null){
            $file_contents = file_get_contents($local_file);
    
            $storage = $this->storage->getStorageSystemByName(StorageSystemName::LOCAL);
            $storage->put("{$this->base_path1}/{$path}/{$file}", $file_contents);
    
            $storage = $this->storage->getStorageSystemByName(StorageSystemName::REPORTS_LOG);
            $storage->put("{$this->base_path2}/{$path}/{$file}", $file_contents);
        }
    }

    public function fromExport(?ExportService $exportService, $log_data, ?string $filename, string $path)
    {

        $this->repo->save(
            $log_data['name'],
            $log_data['direccion'] ?? null,
            $log_data['area'] ?? null,
            $log_data['contacto'] ?? null,
            $log_data['responsable'] ?? null,
            "{$filename}.csv",
            DateTime::createFromFormat("Y-m-d H:i:s", $log_data['ini']),
            DateTime::createFromFormat("Y-m-d H:i:s", $log_data['fin']),
            0,
            $exportService !== null ? 1 : 0,
            $log_data['mensaje'] ?? null
        );

        if($exportService !== null){

            $file_contents = $exportService->getWriter(WriterType::CSV)->getOutput();
    
            $storage = $this->storage->getStorageSystemByName(StorageSystemName::LOCAL2);
            $storage->put("{$this->base_path1}/{$path}/{$filename}.csv", $file_contents);
    
            $storage = $this->storage->getStorageSystemByName(StorageSystemName::REPORTS_LOG);
            $storage->put("{$this->base_path2}/{$path}/{$filename}.csv", $file_contents);
        }
    }
}
