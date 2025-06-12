<?php

namespace AMovil\Reports\DetalleLineas\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\DetalleLineas\Domain\DetalleLineasRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use DateTime;

class DetalleLineasExporter
{
    private $repo;
    private $authService;

    public function __construct(DetalleLineasRepository $repo, AuthService $authService)
    {
        $this->repo = $repo;
        $this->authService = $authService;
    }

    public function __invoke(FileInput $file)
    {
        $responseFile = $this->repo->getReportBy($this->authService->getUserIdentifier(), $file);
        $strNow = (new DateTime())->format("YmdHis");
        return Response::respData([
            "filename" => "detalle_lineas_{$strNow}.xlsx",
            "type" => "xlsx",
            "content" => $responseFile->getFilePath()
        ]);
    }
}
