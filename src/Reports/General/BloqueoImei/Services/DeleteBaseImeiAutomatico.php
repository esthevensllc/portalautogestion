<?php

namespace AMovil\Reports\General\BloqueoImei\Services;

use AMovil\Reports\General\BloqueoImei\Domain\BloqueoImeiRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DeleteBaseImeiAutomatico
{
    private $repo;

    public function __construct(BloqueoImeiRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($file)
    {
        $inputFile = $this->getDataFromFile($file);
        $this->repo->deleteBaseImeiAutomatico($inputFile["values"]);
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
                $values[] = trim($sheet->getCellByColumnAndRow(1, $i)->getValue());
            }
        }
        return ["filename" => $file->getClientOriginalName(), "values" => $values];
    }
}
