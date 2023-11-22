<?php

namespace AMovil\Reports\BloqueoControlReg\Services;

use AMovil\Reports\BloqueoControlReg\Domain\BloqueoControlRegLogRepository;
use AMovil\Reports\BloqueoControlReg\Domain\BloqueoControlRegRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Exception;

class DownloadBloqueoDocument
{
    private $repo;
    private $logRepo;

    public function __construct(
        BloqueoControlRegRepository $repo,
        BloqueoControlRegLogRepository $logRepo,
        ExportService $exportService
    ) {
        $this->repo = $repo;
        $this->logRepo = $logRepo;
        $this->exportService = $exportService;
    }

    public function __invoke($id)
    {
        $report = $this->repo->findReporteDAPU($id);
        // dd($report);
        if($report === null){
            $report = $this->logRepo->findFileContentById($id);
        }
        if($report === null){
            throw new Exception("El archivo no existe");
        }
        $documentos = $this->logRepo->getByCriteria([["id", $id]]);

        $content = base64_decode($report->filecontent);
        return Response::respData([
            "filename" => $documentos[0]->filename,
            "type" => str_ends_with($documentos[0]->filename, ".pdf") ? "pdf" : "xlsx",
            "content" => $content
        ]);
    }

    public function logImei($id) : Response
    {
        $data = $this->repo->getReporte($id);

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
