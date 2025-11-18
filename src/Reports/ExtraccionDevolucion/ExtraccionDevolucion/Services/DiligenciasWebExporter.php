<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\ExtraccionRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style\Border;

class DiligenciasWebExporter
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
    
    public function byCorreo($ticket)
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
            "customer_full_name" => ["label" => "CUSTOMER_FULL_NAME"],
            "nro_documento" => ["label" => "NRO_DOCUMENTO"],
            "correo" => ["label" => "CORREO"],
        ];
        $data = $this->extraccionRepo->getReporteDiligenciasWebCorreo($ticket);
        $this->exportService->loadData($headers, $data, $options);

        $content = $this->exportService->getWriter(WriterType::XLSX)->stream();
        $strNow = (new DateTime())->format("YmdHis");
    
        return Response::respData([
            "filename" => "DILIGENCIAS_WEB_CORREO_TK{$ticket}.xlsx",
            "type" => "xlsx",
            "content" => $content,
        ]);
    }

    public function byDocumento($ticket)
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
            "customer_full_name" => ["label" => "CUSTOMER_FULL_NAME"],
            "tipo_documento" => ["label" => "TIPO_DOCUMENTO"],
            "nro_documento" => ["label" => "NRO_DOCUMENTO"],
            "mto_total_dev_igv" => ["label" => "MTO_TOTAL_DEV_IGV"],
        ];
        $data = $this->extraccionRepo->getReporteDiligenciasWebDocumento($ticket);
        $this->exportService->loadData($headers, $data, $options);

        $content = $this->exportService->getWriter(WriterType::XLSX)->stream();
        $strNow = (new DateTime())->format("YmdHis");
    
        return Response::respData([
            "filename" => "DILIGENCIAS_WEB_DOCUMENTO_TK{$ticket}.xlsx",
            "type" => "xlsx",
            "content" => $content,
        ]);
    }
}
