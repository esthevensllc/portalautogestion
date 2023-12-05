<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\ExtraccionRepository;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CargarReporte
{
    private $repo;
    
    public function __construct(ExtraccionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($ticket, $departamento, $excel, $tipo)
    {
        if($tipo === "1"){
            $data = $this->getDataFromExcel($excel);
            $this->repo->updateReporte($ticket, $departamento, $data);
        }else{
            $data = $this->getDataFromLog($excel);
            $this->repo->saveAcreditacionPrepago($ticket, $departamento, $data);
        }
    }

    private function getDataFromExcel($excel)
    {
        $values = [];
        if ($excel !== null) {
            $reader = IOFactory::createReader('Xlsx');
            $spreedsheet = $reader->load($excel->getPathname());
            $this->validateExcel($spreedsheet);
            $sheet = $spreedsheet->getSheet(1);
            $highestRow = $sheet->getHighestRow();
            for ($i=2; $i <= $highestRow; $i++) {
                $row = [];
                $row["ticket"] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
                $row["msisdn"] = $sheet->getCellByColumnAndRow(2, $i)->getValue();
                // $row["mto_dev_facturacion"] = $sheet->getCellByColumnAndRow(19, $i)->getOldCalculatedValue();
                $row["mto_dev_facturacion"] = $sheet->getCellByColumnAndRow(19, $i)->getValue();
                if(str_starts_with($row["mto_dev_facturacion"], "=")){
                    $row["mto_dev_facturacion"] = $sheet->getCellByColumnAndRow(19, $i)->getOldCalculatedValue();
                }
                $row["mto_dev"] = $sheet->getCellByColumnAndRow(20, $i)->getValue();
                if(str_starts_with($row["mto_dev"], "=")){
                    $row["mto_dev"] = $sheet->getCellByColumnAndRow(20, $i)->getOldCalculatedValue();
                }
                $row["factura_aplicada"] = $sheet->getCellByColumnAndRow(21, $i)->getValue();
                $row["fecha_devolucion"] = $this->formatExcelDate($sheet->getCellByColumnAndRow(22, $i)->getValue());
                $row["fecha_registro_devolucion"] = $this->formatExcelDate($sheet->getCellByColumnAndRow(23, $i)->getValue());
                $row["observacion"] = $sheet->getCellByColumnAndRow(24, $i)->getValue();
                $row["fecha_baja_facturacion"] = $this->formatExcelDate($sheet->getCellByColumnAndRow(25, $i)->getValue());
                $values[] = $row;
            }
        }
        return $values;
    }

    private function validateExcel(Spreadsheet $spreedsheet)
    {
        $sheetCount = $spreedsheet->getSheetCount();
        if($sheetCount !== 2){
            throw new Exception("El número de hojas deven ser dos");
        }
        $sheet = $spreedsheet->getSheet(1);
        $firstRowCount = $sheet->getHighestColumn(1);
        if($firstRowCount !== "Y"){
            throw new Exception("El número de columnas deven ser 25(columna 'Y' como máximo)");
        }
    }

    private function formatExcelDate($strDate)
    {
        $output = null;
        if(is_numeric($strDate)){
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($strDate);
            $output = $date->format("Y-m-d")." 00:00:00";
        }else{
            $date = DateTime::createFromFormat('d/m/Y', $strDate);
            if($date){
                $output = $date->format("Y-m-d")." 00:00:00";
            }
        }
        return $output;
    }

    private function getDataFromLog($file)
    {
        $values = [];
        if ($file !== null) {
            // $file_contents = file_get_contents($file->getPathname());
            $file = fopen($file->getPathname(), "r");
            $row = [];
            $count = 1;
            while(!feof($file)){
                $line = fgets($file);
                if(str_contains($line, "#@TRANSACTION")){
                    $row = [];
                }else if(str_contains($line, "#@END_TRANSACTION")){
                    $values[] = $row;
                }else{
                    $line_values = [];
                    $output = str_replace([
                        "#CA::Modify:PackageItem(",
                        "CA::Modify:PackageItem(",
                        ");",
                        "=\"",
                        "\",",
                        ",",
                        "\n",
                        "\""
                    ], ["","","","=",",","&", "", ""], $line);
                    $output = str_replace(["#CA::Modify:CustomerLifeCycleState(", "CA::Modify:CustomerLifeCycleState("], ["",""], $output);
                    parse_str($output, $line_values);
                    $row = array_merge($row, $line_values);
                }
                // $count++;
                // if($count > 20){
                //     break;
                // }
            }
            fclose($file);
        }
        return $values;
    }
}
