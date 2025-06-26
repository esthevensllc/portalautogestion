<?php

namespace AMovil\Reports\DetalleLineas\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\DetalleLineas\Domain\DetalleLineasRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
        if (!in_array($file->getExtension(), ["csv","txt"])) {
            return new Response(["message" => "Solo se puede subir archivos .csv y .txt"]);
        }
        if ($this->getCountRowsFromExcel($file) > 1000000) {
            return new Response(["message" => "El archivo csv no puede superar el 1,000,000 de filas"]);
        }
        $responseFile = $this->repo->getReportBy($this->authService->getUserIdentifier(), $file);
        $strNow = (new DateTime())->format("YmdHis");
        return Response::respData([
            "filename" => "detalle_lineas_{$strNow}.xlsx",
            "type" => "xlsx",
            "content" => $responseFile->getFilePath()
        ]);
    }

    public function getCountRowsFromExcel(FileInput $file): int {
        $rows = 0;
        $handle = fopen($file->getFilePath(), "r");
        if ($handle) {
            while (!feof($handle)) {
                fgets($handle);
                $rows++;
            }
            fclose($handle);
        }
        return $rows;
    }
}
