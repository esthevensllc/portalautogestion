<?php

namespace AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Domain\InformeCCPPRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use DateTime;
use Exception;

class LoadInformeCCPP
{
    private $repo;
    private $authService;
    private $userIdentifier;
    private $localStorage;
    private $baseStoragePath = "/space/reportes/informes_ccpp";

    public function __construct(
        InformeCCPPRepository $repo,
        AuthService $authService,
        StorageService $storageService
    ) {
        $this->repo = $repo;
        $this->authService = $authService;
        $this->localStorage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
    }

    public function __invoke(FileInput $file)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $filename = str_replace(" ", "_", $file->getFilename());
        $criteriaResponse = $this->repo->getByCriteria(["filename.eq.{$filename}"]);
        if (count($criteriaResponse["data"]) > 0) {
            throw new Exception("El archivo ya fue cargado anteriormente");
        }
        $this->repo->create($this->userIdentifier, $file->getFilename(), new DateTime());
        $this->localStorage->put("{$this->baseStoragePath}/{$filename}", file_get_contents($file->getFilePath()));
    }
}
