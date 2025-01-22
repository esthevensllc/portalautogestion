<?php

namespace AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaRepository;
use AMovil\Shared\Application\FileInput;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// use AMovil\Shared\FileStorage\Domain\StorageService;
// use AMovil\Shared\FileStorage\Domain\StorageSystemName;

class UploadReportExtraccionFija
{
    // private $storage;
    private $storageBasepath = "/space/reportes/EXTRACCION_DEV_FIJA";
    private $repo;

    /*public function __construct(StorageService $storageService)
    {
        $this->storage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
    }*/
    public function __construct(ExtraccionDevFijaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($ticket, $departamento, FileInput $file)
    {
        
        $data = $this->getDataFromExcel($file);
        
        $this->repo->updateReporte($ticket, $departamento, $data);
        
        copy($file->getFilePath(), "{$this->storageBasepath}/".$file->getFilename());
    }

    private function getDataFromExcel(FileInput $excel)
    {
        $values = [];
        if ($excel !== null) {
            $reader = IOFactory::createReader('Xlsx');
            $spreedsheet = $reader->load($excel->getFilePath());
            $this->validateExcel($spreedsheet);
            $sheet = null;
            if ($spreedsheet->getSheetCount() > 1) {
                $sheet = $spreedsheet->getSheet(1);
            } else {
                $sheet = $spreedsheet->getSheet(0);
            }
            $highestRow = $sheet->getHighestRow();
            for ($i=2; $i <= $highestRow; $i++) {
                $row = [];
                $row["ticket"] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
                $row["msisdn"] = $sheet->getCellByColumnAndRow(2, $i)->getValue();
                $row["fuente"] = $sheet->getCellByColumnAndRow(16, $i)->getValue();
                $row["mto_dev_facturacion"] = $sheet->getCellByColumnAndRow(20, $i)->getValue();
                if(str_starts_with($row["mto_dev_facturacion"], "=")){
                    $row["mto_dev_facturacion"] = $sheet->getCellByColumnAndRow(20, $i)->getOldCalculatedValue();
                }
                $row["mto_dif_facturacion"] = $sheet->getCellByColumnAndRow(21, $i)->getValue();
                if(str_starts_with($row["mto_dif_facturacion"], "=")){
                    $row["mto_dif_facturacion"] = $sheet->getCellByColumnAndRow(21, $i)->getOldCalculatedValue();
                }
                $row["factura_aplicada"] = $sheet->getCellByColumnAndRow(22, $i)->getValue();
                $row["fecha_devolucion"] = $this->formatExcelDate($sheet->getCellByColumnAndRow(23, $i)->getValue());
                $row["fecha_registro_devolucion"] = $this->formatExcelDate($sheet->getCellByColumnAndRow(24, $i)->getValue());
                $row["observacion"] = $sheet->getCellByColumnAndRow(25, $i)->getValue();
                $row["fecha_baja"] = $this->formatExcelDate($sheet->getCellByColumnAndRow(26, $i)->getValue());
                $values[] = $row;
            }
        }
        return $values;
    }

    private function validateExcel(Spreadsheet $spreedsheet)
    {
        $sheetCount = $spreedsheet->getSheetCount();
        if($sheetCount > 2){
            throw new Exception("No se puede cargar mas de dos hojas");
        }
        $sheet = $spreedsheet->getSheet(1);
        $firstRowCount = $sheet->getHighestColumn(1);
        if($firstRowCount !== "Z"){
            throw new Exception("El número de columnas deven ser 26(columna 'Y' como máximo)");
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
}
