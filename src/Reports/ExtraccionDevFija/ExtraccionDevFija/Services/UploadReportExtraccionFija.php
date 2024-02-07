<?php

namespace AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services;

use AMovil\Shared\Application\FileInput;
// use AMovil\Shared\FileStorage\Domain\StorageService;
// use AMovil\Shared\FileStorage\Domain\StorageSystemName;

class UploadReportExtraccionFija
{
    // private $storage;
    private $storageBasepath = "/space/reportes/EXTRACCION_DEV_FIJA";

    /*public function __construct(StorageService $storageService)
    {
        $this->storage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
    }*/

    public function __invoke(FileInput $file)
    {
        copy($file->getFilePath(), "{$this->storageBasepath}/".$file->getFilename());
    }
}
