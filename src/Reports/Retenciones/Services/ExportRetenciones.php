<?php

namespace AMovil\Reports\Retenciones\Services;

use AMovil\Reports\Retenciones\Domain\RetencionesRepository;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExportRetenciones
{
    private $repo;
    public function __construct(RetencionesRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($excel)
    {
        $data = $this->getDataFromExcel($excel);
        $result = $this->repo->saveAll($data);
        return $result;
    }

    public function __invokeRutas($excel)
    {
        $data = $this->getDataFromExcelRutas($excel);
        $result = $this->repo->saveAllRutas($data);
        return $result;
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
                $row['dia_load'] = now();
                $row['pk'] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
                $row['operador'] = $sheet->getCellByColumnAndRow(2, $i)->getValue();
                $row['flag_dpto'] = $sheet->getCellByColumnAndRow(3, $i)->getValue();
                $row['flag'] = $sheet->getCellByColumnAndRow(4, $i)->getValue();
                $row['target'] = $sheet->getCellByColumnAndRow(5, $i)->getValue();
                $row['decil'] = $sheet->getCellByColumnAndRow(6, $i)->getValue();
                $row['callcenter'] = $sheet->getCellByColumnAndRow(7, $i)->getValue();
                $row['final'] = $sheet->getCellByColumnAndRow(8, $i)->getValue();
                $row['key_hash'] = "SHA2(CONCAT('".$row['pk']."','".$row['operador']."','".$row['flag_dpto']."',if('".$row['flag']."' is null,'NULL','".$row['flag']."'),'".$row['target']."','".$row['decil']."','".$row['callcenter']."','".$row['final']."'), 256)";
                $values[] = $row;
            }
        }
        return $values;
    }

    private function getDataFromExcelRutas($excel)
    {
        $values = [];
        if ($excel !== null) {
            $reader = IOFactory::createReader('Xlsx');
            $spreedsheet = $reader->load($excel->getPathname());
            $sheet = $spreedsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            for ($i=2; $i <= $highestRow; $i++) {
                $row = [];
                $row['dia_load'] = now();
                $row['callcenter'] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
                $row['operadorfinal'] = $sheet->getCellByColumnAndRow(2, $i)->getValue();
                $row['fileName'] = $sheet->getCellByColumnAndRow(3, $i)->getValue();
                $row['pathName'] = $sheet->getCellByColumnAndRow(8, $i)->getValue();
                $row['key_hash'] = "SHA2(CONCAT('".$row['callcenter']."','".$row['operadorfinal']."','".$row['fileName']."','".$row['pathName']."'), 256)";
                $values[] = $row;
            }
        }
        return $values;
    }
}
