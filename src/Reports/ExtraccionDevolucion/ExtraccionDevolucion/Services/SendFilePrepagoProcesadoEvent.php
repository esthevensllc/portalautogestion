<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository as InformeFallaRepository;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use Ramsey\Uuid\Uuid;

class SendFilePrepagoProcesadoEvent
{
    private $exportPrepago;
    private $storage;
    private $baseStoragePath = "/space/reportes/DEVOLUCIONES_PREPAGO_OSIPTEL/input";

    public function __construct(
        ExportRepPrepago $exportPrepago,
        StorageService $storageService
    ) {
        $this->exportPrepago = $exportPrepago;
        $this->storage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
    }
    
    public function __invoke($ticket, $departamento)
    {
        $response = $this->exportPrepago->__invoke($ticket, $departamento)->data();
        $tempFilePath = $this->saveToTempfile($response);
        $filename = $response["filename"];

        $this->storage->put("{$this->baseStoragePath}/{$filename}", file_get_contents($tempFilePath));
        unlink($tempFilePath);
    }

    public function saveToTempfile($response)
    {
        $tempFilename = sys_get_temp_dir() ."/phpexport-". Uuid::uuid4()->toString().".tsv";
        $fp = fopen($tempFilename, "w");
        fwrite($fp, $response["content"]);
        fclose($fp);
        return $tempFilename;
    }
}
