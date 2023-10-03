<?php

namespace AMovil\Reports\General\ComprobantePago\Services;

use AMovil\Reports\General\ComprobantePago\Domain\ComprobantePagoRepository;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportReporteComprobantePago
{
    private $repo;
    private $exportService;
    private $saveReportLog;

    public function __construct(ComprobantePagoRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke($file): Response
    {
        $dtStart = new DateTime();
        try {
            $values = $this->getDataFromFile($file);
            $data = $this->repo->getReporte($values);
            for ($i=0; $i < count($data); $i++) { 
                $data[$i]->row = $i+1;
                $dtFecha = new DateTime($data[$i]->fecha_emision);
                if($dtFecha){
                    $data[$i]->fecha_emision = $dtFecha->format("d/m/Y");
                }
            }

            $headers = [
                "row" => ["label" => "N°"],
                "plataforma" => ["label" => "PLATAFORMA"],
                "nro_comprobante" => ["label" => "NRO_COMPROBANTE"],
                "fecha_emision" => ["label" => "FECHA_EMISION"],
                "razon_social" => ["label" => "RAZON_SOCIAL"],
                "customer_id" => ["label" => "CUSTOMER_ID"],
                "tipo_comprobante" => ["label" => "TIPO_COMPROBANTE"],
                "idcon" => ["label" => "IDCON"],
                "idcon_asociado_nc_nd" => ["label" => "IDCON_ASOCIADO_NC_ND"],
            ];

            $this->exportService->loadData($headers, $data, [
                'sheetIndex' => 0,
                'title' => "Reporte",
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

            $sheet = $this->exportService->getExportReference()->getActiveSheet();

            $columns_to_autosize = ['B', 'C','D', 'E', 'F', 'G', 'H', 'I'];

            foreach($columns_to_autosize as $col){
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $tempFilename = $this->exportService->getWriter(WriterType::XLSX)->saveToTempfile();
            
            $this->reportLog($tempFilename, $dtStart, new DateTime());
            $content = file_get_contents($tempFilename);
            unlink($tempFilename);
            
            return new Response([], [
                "filename" => $file->getClientOriginalName(),
                "type" => "xlsx",
                "content" => $content
            ]);    
        } catch (\Throwable $th) {
            $this->reportLog(null, $dtStart, new DateTime(), ['mensaje' => $th->getMessage()]);
            throw $th;
        }
    }

    private function getDataFromFile($file)
    {
        $values = [];
        if ($file !== null) {
            $reader = IOFactory::createReader('Xlsx');
            $spreedsheet = $reader->load($file->getPathname());
            $sheet = $spreedsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            for ($i=2; $i <= $highestRow; $i++) {
                $row = [];
                $row["plataforma"] = $sheet->getCellByColumnAndRow(2, $i)->getValue();
                $row["nro_comprobante"] = $sheet->getCellByColumnAndRow(3, $i)->getValue();
                $row["fecemi"] = $sheet->getCellByColumnAndRow(5, $i)->getValue();
                $row["fecemi"] = $this->formatExcelDate($row["fecemi"]);
                $values[] = $row;
            }
        }
        return $values;
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

    private function reportLog($allFilename, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        $filename = "COMPROBANTE_PAGO_".$ini->format('YmdHis').".xlsx";
        $data = array_merge([
            'name' => 'COMPROBANTE_PAGO',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => 'comprobante-pago',
            "filename" => $allFilename !== null ? $filename : null
        ], $extra_data);
        $this->saveReportLog->__invoke($data, $allFilename, 'COMPROBANTE_PAGO');
    }
}
