<?php

namespace AMovil\Reports\MantenimientoCeldas\Mantenimiento\Services;

use AMovil\Reports\MantenimientoCeldas\Mantenimiento\Domain\MantenimientoCeldaRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportMantenimientoCeldas
{
    private $repo;
    private $exportService;

    public function __construct(MantenimientoCeldaRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($ticket, $departamento): Response
    {
        $data = $this->repo->getReporte($ticket, $departamento);

        $options = [
            "sheetIndex" => 0,
            'title' => "REPORTE",
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
            "id_cliente" => ["label" => "ID_CLIENTE"],
            "tipo_doc" => ["label" => "TIPO_DOC"],
            "nro_documento" => ["label" => "NRO_DOCUMENTO"],
            "nombres_apellidos" => ["label" => "NOMBRES_APELLIDOS"],
            "msisdn" => ["label" => "MSISDN"],
            "departamento" => ["label" => "DEPARTAMENTO"],
        ];
        $this->exportService->loadData($headers, $data, $options);

        $content = $this->exportService->getWriter(WriterType::XLSX)->getOutput();

        return Response::respData([
            "filename" => "MANTENIMIENTO_CELDAS_{$ticket}.xlsx",
            "type" => "xlsx",
            "content" => $content
        ]);
    }
    
}
