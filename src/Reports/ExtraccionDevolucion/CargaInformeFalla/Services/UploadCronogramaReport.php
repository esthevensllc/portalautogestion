<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use Illuminate\Http\UploadedFile;

class UploadCronogramaReport
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

    public function __invoke($numReporte, UploadedFile $file)
    {
        $data = $this->repo->findInputFor($numReporte);
        if (count($data) === 0) {
            return Response::respError(["message" => "El informe de fallas no existe"]);
        }

        $informe = $data[0];
        $filename = $this->sanitizeFilename($file->getClientOriginalName());
        if ($filename === '') {
            return Response::respError(["message" => "El nombre del archivo de cronograma no es válido"]);
        }

        if (!empty($informe->cronograma_file) && $informe->cronograma_file !== $filename) {
            $this->storage->delete($this->buildStoragePath($numReporte, $informe->cronograma_file));
        }

        $this->storage->put($this->buildStoragePath($numReporte, $filename), file_get_contents($file->getPathname()));
        $this->repo->updateCronogramaFile($numReporte, $filename);

        return Response::respData([
            "numero_de_reporte" => $numReporte,
            "cronograma_file" => $filename,
        ]);
    }

    private function buildStoragePath($numReporte, $filename)
    {
        return "{$this->baseStoragePath}/{$numReporte}_{$filename}";
    }

    private function sanitizeFilename($filename)
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $filename);
        return trim($filename, '._-');
    }
}
