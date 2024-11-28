<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\ExtraccionRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportReporteMsisdn
{
    private $repo;
    private $exportService;
    public function __construct(ExtraccionRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
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
            "num_reporte" => ["label" => "NUM_REPORTE"],
            "ticket" => ["label" => "TICKET"],
            "msisdn" => ["label" => "MSISDN"],
            "fecha_carga" => ["label" => "FECHA_CARGA"],
            "celda" => ["label" => "CELDA"],
            "fecha_corte" => ["label" => "FECHA_CORTE"],
            "departamento" => ["label" => "DEPARTAMENTO"],
            "provincia" => ["label" => "PROVINCIA"],
            "distrito" => ["label" => "DISTRITO"],
            "observacion" => ["label" => "OBSERVACION"],
        ];
        $reporte = $this->repo->getReporteMsisdn($ticket);
        $this->exportService->loadData($headers, $reporte, $options);

        $content = $this->exportService->getWriter(WriterType::XLSX)->stream();
        $strNow = (new DateTime())->format("YmdHis");
    
        return Response::respData([
            "filename" => "REPORTE_MSISDN_{$strNow}.xlsx",
            "type" => "xlsx",
            "content" => $content,
        ]);
    }
}
