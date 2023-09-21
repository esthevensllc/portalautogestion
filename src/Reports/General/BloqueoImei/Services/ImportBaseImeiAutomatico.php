<?php

namespace AMovil\Reports\General\BloqueoImei\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\General\BloqueoImei\Domain\BloqueoImeiRepository;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportBaseImeiAutomatico
{
    private $repo;
    private $authService;

    public function __construct(BloqueoImeiRepository $repo, AuthService $authService)
    {
        $this->repo = $repo;
        $this->authService = $authService;
    }

    public function __invoke($file)
    {
        $filename = $file->getClientOriginalName();

        if(!$this->validateFilename($filename)){
            throw new Exception("El nombre del archivo no es valido");
        }

        $reportLog = $this->repo->getAutomaticReportLogByCriteria([
            ["filename", $filename],
            ["delete_flag", 0]
        ]);
        if(count($reportLog) > 0){
            throw new Exception("No se puede procesar el mismo archivo nuevamente");
        }

        $inputFile = $this->getDataFromFile($file);
        $id = (new DateTime())->format("YmdHis");
        $this->repo->importBaseImeiAutomatico($id, $filename, $inputFile["values"]);
        $this->repo->saveAutomaticReportLog(
            $id,
            $this->authService->getUserIdentifier(),
            $filename,
            count($inputFile["values"])
        );
    }

    private function getDataFromFile($file)
    {
        $values = [];
        if ($file !== null) {
            $reader = IOFactory::createReader('Csv');
            $spreedsheet = $reader->load($file->getPathname());
            $sheet = $spreedsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            for ($i=1; $i <= $highestRow; $i++) {
                $values[] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
            }
        }
        return ["filename" => $file->getClientOriginalName(), "values" => $values];
    }

    private function validateFilename($filename){
        return preg_match('/^[A-Za-z0-9_\.]+$/', $filename) === 1;
    }
}
