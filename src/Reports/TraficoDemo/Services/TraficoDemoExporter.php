<?php

namespace AMovil\Reports\TraficoDemo\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\TraficoDemo\Domain\TraficoDemoRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use DateTime;

class TraficoDemoExporter
{
    private $repo;
    private $authService;

    public function __construct(TraficoDemoRepository $repo, AuthService $authService)
    {
        $this->repo = $repo;
        $this->authService = $authService;
    }

    public function __invoke(string $fechaIni, string $fechaFin, ?FileInput $file)
    {
        $dtFechaIni = DateTime::createFromFormat('Y-m-d', $fechaIni);
        $dtFechaFin = DateTime::createFromFormat('Y-m-d', $fechaFin);
        $responseFile = $this->repo->getReportBy($this->authService->getUserIdentifier(), $dtFechaIni, $dtFechaFin, $file);
        $strNow = (new DateTime())->format("YmdHis");
        return Response::respData([
            "filename" => "trafico_demo_{$strNow}.xlsx",
            "type" => "xlsx",
            "content" => $responseFile->getFilePath()
        ]);
    }
}
