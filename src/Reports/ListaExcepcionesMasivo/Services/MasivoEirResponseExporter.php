<?php

namespace AMovil\Reports\ListaExcepcionesMasivo\Services;

use AMovil\Reports\ListaExcepcionesMasivo\Domain\ListaExcepcionesMasivoRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use PhpOffice\PhpSpreadsheet\Style\Border;

class MasivoEirResponseExporter
{
    private $repo;
    private $exportService;

    public function __construct(ListaExcepcionesMasivoRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($id): Response
    {
        $document = $this->repo->getByCriteria([["eir_id", $id]]);
        if(count($document) === 0){
            return new Response(["message" => "El reporte no existe"]);
        }
        if((int) $document[0]->processed !== 1){
            return new Response(["message" => "El reporte aun no fue procesado"]);
        }
        $data = $this->repo->getEirReponseByEirId($id);

        $options = [
            "rowType" => "object",
            "sheetIndex" => 0,
            'title' => "Log IMEI",
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
            "v1" => ["label" => "V1"],          
            "v2" => ["label" => "V2"],          
            "v3" => ["label" => "V3"],          
            "v4" => ["label" => "V4"],          
            "v5" => ["label" => "V5"],         
            "status" => ["label" => "STATUS"],      
            "v7" => ["label" => "V7"],          
            "v8" => ["label" => "V8"],          
            "v9" => ["label" => "V9"]
        ];

        $this->exportService->loadData($headers, $data, $options);
        $exportContent = $this->exportService->getWriter(WriterType::XLSX)->getOutput();

        return new Response([], [
            "filename" => $id.".xlsx",
            "type" => "xlsx",
            "content" => $exportContent,
        ]);
    }
}
