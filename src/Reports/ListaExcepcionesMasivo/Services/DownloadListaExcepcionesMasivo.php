<?php

namespace AMovil\Reports\ListaExcepcionesMasivo\Services;

use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;

class DownloadListaExcepcionesMasivo
{
    private $localBaseStoragePath = "/space/reportes/lista_excepciones_masivo_control_regulatorio";
    private $localStorage;

    public function __construct(StorageService $storageService)
    {
        $this->localStorage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
    }

    public function __invoke($filename): Response
    {
        $file = new FileInput("{$this->localBaseStoragePath}/{$filename}", $filename);
        return new Response([], [
            "filename"=> $file->getFilename(),
            "type"=> $file->getExtension(),
            "content" => $this->localStorage->get("{$this->localBaseStoragePath}/{$filename}"),
        ]);
    }
}
