<?php

namespace AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use AMovil\Shared\Exports\Domain\WriterType;
use AMovil\Shared\Exports\Domain\ExportService;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Ramsey\Uuid\Uuid;
use DateTime;

class SendFilePrepagoProcesadoEvent
{
    private $repo;
    private $exportService;
    private $storage;
    private $baseStoragePath = "/space/reportes/DEVOLUCIONES_PREPAGO_OSIPTEL/input";

    public function __construct(
        ExtraccionDevFijaRepository $repo,
        StorageService $storageService,
        ExportService $exportService
    ) {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->storage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
    }
    
    public function __invoke($ticket)
    {
        $reporte = $this->repo->getReportePrepago($ticket);
        if (count($reporte) > 0) {
            $response = $this->exportReportePrepago($ticket, $reporte)->data();
            $tempFilePath = $response['filepath'];
            $filename = $response["filename"];
    
            $this->storage->put("{$this->baseStoragePath}/{$filename}", file_get_contents($tempFilePath));
            unlink($tempFilePath);
        }
    }

    private function exportReportePrepago($ticket, $reporte){
        $dtStart = new DateTime();
        try {
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
                "msisdn_devol" => ["label" => "MSISDN_DEVOL"],
                "centimos" => ["label" => "CENTIMOS"],
                "glosa" => ["label" => "GLOSA"],
            ];
            $this->exportService->loadData($headers, $reporte, $options);
    
            $content = $this->exportService->getWriter(WriterType::CSV)->getOutput();
            $content = str_replace(['"',","], ['','|'], $content);

            $tempFilename = storage_path('app/public')."/".Uuid::uuid4()->toString().".tsv";
            $fp = fopen($tempFilename, "w");
            fwrite($fp, $content);
            fclose($fp);

            // $this->reportLog($tempFilename, $dtStart, new DateTime(), [
            //     "filename" => "Base_Devolucion_Prepago_TK{$ticket}.tsv"
            // ]);
    
            return new Response([], [
                "filename" => "Base_Devolucion_Prepago_TK{$ticket}_MANTENIMIENTO.tsv",
                "type" => "csv",
                "filepath" => $tempFilename,
            ]);
        } catch (\Throwable $th) {
            // $this->reportLog(null, $dtStart, new DateTime(), ['mensaje' => $th->getMessage()]);
            throw $th;
        }
    }
}
