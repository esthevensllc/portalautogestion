<?php

namespace AMovil\Reports\ExtraccionDevolucion\CarteraGobierno\Services;

use AMovil\Reports\ExtraccionDevolucion\CarteraGobierno\Domain\CarteraGobiernoRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CargarCarteraGobierno
{
    private $repo;
    public function __construct(CarteraGobiernoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($excel)
    {
        $data = $this->getDataFromExcel($excel);
        $this->repo->saveAll($data);
    }

    private function getDataFromExcel($excel)
    {
        $values = [];
        if ($excel !== null) {
            $reader = IOFactory::createReader('Xlsx');
            $spreedsheet = $reader->load($excel->getPathname());
            $sheet = $spreedsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            for ($i=2; $i <= $highestRow; $i++) {
                $row = [];
                $row["ruc"] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
                $row["entidad_razon_social"] = $sheet->getCellByColumnAndRow(2, $i)->getValue();
                $values[] = $row;
            }
        }
        return $values;
    }
}
