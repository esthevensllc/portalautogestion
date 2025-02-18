<?php

namespace AMovil\Reports\ListaExcepcionesArt25\Services;

use AMovil\Reports\ListaExcepcionesArt25\Domain\ListaExcepcionesArt25Repository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportListaExcepcionesArt25
{
    private $repo;
    private $exportService;

    public function __construct(ListaExcepcionesArt25Repository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($imeis, $exportType)
    {
        $results = [];
        if($imeis === null){
            $results = $this->repo->findLast();
        }else{
            $results = $this->repo->getByImei($imeis);
        }
        $content = $this->export($results, $exportType);
        $strDate = (new DateTime())->format("Ymd");
        return Response::respData([
            "filename" => "Lista_Excepciones_Art25_{$strDate}.".strtolower($exportType),
            "content" => $content,
        ]);
    }

    public function fromFile(FileInput $file, $exportType){
        if(!in_array($file->getExtension(), ["csv"])){
            throw new Exception("La extension {$file->getExtension()} no es valida");
        }
        $extension = ucfirst($file->getExtension());
        $reader = IOFactory::createReader($extension);
        $spreedsheet = $reader->load($file->getFilePath());
        $sheet = $spreedsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();

        $data = [];
        for ($i=1; $i <= $highestRow; $i++) {
            $data[] = trim($sheet->getCellByColumnAndRow(1, $i)->getValue());
        }
        
        return $this->__invoke($data, $exportType);
    }

    private function export($data, $exportType)
    {
        $headers = [
            "transact_date" => ["label" => "TRANSACT_DATE"],
            "imei" => ["label" => "IMEI"],
            "operacion" => ["label" => "OPERACION"],
            "msisdn" => ["label" => "MSISDN"],
            "imsi" => ["label" => "IMSI"],
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
            'title' => "REPORTE",
            'styles' => [
                'header' => [
                    'font' => ['bold' => true, 'size' => 9],
                    'borders'=> [
                        'allBorders' => ['borderStyle' => SpreadsheetStyle\Border::BORDER_THIN, 'color' => array('rgb'=>'000000')]
                    ]
                ],
                'body' => [
                    'font' => ['size' => 9],
                ]
            ]
        ]);
        return $this->exportService->getWriter($exportType)->getOutput();
    }
}
