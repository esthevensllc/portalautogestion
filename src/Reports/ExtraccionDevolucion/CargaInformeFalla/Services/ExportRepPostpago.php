<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\ExtraccionRepository;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Ramsey\Uuid\Uuid;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportRepPostpago
{
    private $repo;
    private $exportService;
    private $saveReportLog;
    public function __construct(ExtraccionRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke($ticketOsiptel, $departamento)
    {
        $dtStart = new DateTime();
        try {
            $options = [
                "sheetIndex" => 0,
                'title' => $ticketOsiptel,
                'styles' => [
                    'header' => [
                        'font' => ['bold' => true, 'size' => 9],
                        'borders'=> [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => array('rgb'=>'000000')
                            ]
                        ]
                    ],
                    'body' => [
                        'font' => ['size' => 9]
                    ]
                ]
            ];

            $numberFormat = ['numberFormat' => ['formatCode' => SpreadsheetStyle\NumberFormat::FORMAT_NUMBER_00]];
    
            $headers = [
                "ticket" => ["label" => "TICKET"],
                "msisdn" => ["label" => "MSISDN"],
                "msisdn_devol" => ["label" => "MSISDN_DEVOL"],
                "cargo_linea" => ["label" => "CARGO_LINEA"],
                "cargo_linea_igv" => ["label" => "CARGO_LINEA_IGV", 'bodyStyles' => $numberFormat],
                "mto_dev" => ["label" => "MTO_DEV", 'bodyStyles' => $numberFormat],
                "mto_dev_igv" => ["label" => "MTO_DEV_IGV", 'bodyStyles' => $numberFormat],
                "interes" => ["label" => "INTERES", 'bodyStyles' => $numberFormat],
                "tasa" => ["label" => "TASA", 'bodyStyles' => $numberFormat],
                "mto_total_dev_igv" => ["label" => "MTO_TOTAL_DEV_IGV", 'bodyStyles' => $numberFormat],
                "custcode" => ["label" => "CUSTCODE"],
                "customer_id" => ["label" => "CUSTOMER_ID"],
                "co_id_devolver" => ["label" => "CO_ID_DEVOLVER"],
                "ciclofacturacion" => ["label" => "CICLOFACTURACION"],
                "fuente" => ["label" => "FUENTE"],
                "fecha_alta" => ["label" => "FECHA_ALTA"],
                "fecha_activacion" => ["label" => "FECHA_ACTIVACION"],
                "glosa" => ["label" => "GLOSA"],
            ];
            $reporte = $this->repo->getPostpagoBy($ticketOsiptel, $departamento);
            $this->exportService->loadData($headers, $reporte, $options);
    
            $tempFilename = storage_path('app/public')."/".Uuid::uuid4()->toString().".xlsx";
            $this->exportService->getWriter(WriterType::XLSX)->save($tempFilename);

            $this->reportLog($tempFilename, $dtStart, new DateTime(), [
                "filename" => "Base_Devolucion_Postpago_TK{$ticketOsiptel}.xlsx"
            ]);

            $content = file_get_contents($tempFilename);
            unlink($tempFilename);
    
            return new Response([], [
                "filename" => "Base_Devolucion_Postpago_TK{$ticketOsiptel}.xlsx",
                "type" => "xlsx",
                "content" => $content,
            ]);
        } catch (\Throwable $th) {
            $this->reportLog(null, $dtStart, new DateTime(), ['mensaje' => $th->getMessage()]);
            throw $th;
        }
    }

        
    private function reportLog($allFilename, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        // $filename = "EXTRACCION_".$ini->format('YmdHis').".xlsx";
        $data = array_merge([
            'name' => 'EXTRACCION_REPORTES',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => 'extraccion-devolucion.reportes',
            // "filename" => $allFilename !== null ? $filename : null
        ], $extra_data);
        $this->saveReportLog->__invoke($data, $allFilename, 'EXTRACCION_REPORTES');
    }
}
