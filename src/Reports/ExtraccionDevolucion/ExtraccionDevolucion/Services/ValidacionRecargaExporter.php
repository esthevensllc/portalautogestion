<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\ExtraccionRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ValidacionRecargaExporter
{
    private $extraccionRepo;
    private $exportService;

    public function __construct(
        ExtraccionRepository $extraccionRepo,
        ExportService $exportService
    ) {
        $this->extraccionRepo = $extraccionRepo;
        $this->exportService = $exportService;
    }
    
    public function __invoke($ticket)
    {
        $options = [
            "sheetIndex" => 0,
            'title' => $ticket,
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

        $headers = [
            "ticket" => ["label" => "TICKET"],
            "msisdn" => ["label" => "MSISDN"],
            "cantidad" => ["label" => "CANTIDAD"],
            "recharge_date" => ["label" => "RECHARGE_DATE"],
            "recarga" => ["label" => "RECARGA"],
            "msisdn_devolver" => ["label" => "MSISDN_DEVOLVER"],
            "usage_str3" => ["label" => "USAGE_STR3"],
        ];
        $data = $this->extraccionRepo->getPrepagoLog($ticket);
        $this->exportService->loadData($headers, $data, $options);

        $content = $this->exportService->getWriter(WriterType::XLSX)->stream();
        $strNow = (new DateTime())->format("YmdHis");
    
        return Response::respData([
            "filename" => "REPORTE_PREPAGO_LOG_{$strNow}.xlsx",
            "type" => "xlsx",
            "content" => $content,
        ]);
    }
}
