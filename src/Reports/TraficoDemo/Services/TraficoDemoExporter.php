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

    public function __invoke($anio, $mes, ?FileInput $file)
    {
        $responseFile = $this->repo->getReportBy($this->authService->getUserIdentifier(), $anio, $mes, $file);
        $strNow = (new DateTime())->format("YmdHis");
        return Response::respData([
            "filename" => "trafico_demo_{$strNow}.xlsx",
            "type" => "xlsx",
            "content" => $responseFile->getFilePath()
        ]);
    }
}
