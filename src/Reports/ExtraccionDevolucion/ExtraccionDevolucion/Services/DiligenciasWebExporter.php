<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\ExtraccionRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Ramsey\Uuid\Uuid;
use ZipArchive;

class DiligenciasWebExporter
{
    private $extraccionRepo;
    private $exportService;
    private $storage_path;

    public function __construct(
        ExtraccionRepository $extraccionRepo,
        ExportService $exportService
    ) {
        $this->extraccionRepo = $extraccionRepo;
        $this->exportService = $exportService;
        $this->storage_path = storage_path('app/public');
    }
    
    public function byCorreo($tickets)
    {
        $ticketsArray = explode(",", str_replace(" ", "", $tickets));
        $strNow = (new DateTime())->format("Ymd");
        $labelExcel = count($ticketsArray) > 1 ? $strNow : "TK".$tickets;

        if (count($ticketsArray) > 100) {
            return Response::respError(["message" => "No se puede ingresar mas de 100 tickets"]);
        }

        $ticketsArray = $this->extraccionRepo->getTicketsWithDevolucionWeb($ticketsArray);
        if (count($ticketsArray) === 0) {
            return Response::respError(["message" => "El ticket no tiene usuarios afectados con devoluciones web"]);
        }

        $options = [
            "sheetIndex" => 0,
            'title' => $labelExcel,
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
            "customer_full_name" => ["label" => "CUSTOMER_FULL_NAME"],
            "nro_documento" => ["label" => "NRO_DOCUMENTO"],
            "correo" => ["label" => "CORREO"],
        ];
        $data = $this->extraccionRepo->getReporteDiligenciasWebCorreo($ticketsArray);
        if (count($data) === 0) {
            return Response::respError(["message" => "No hay afectados"]);
        }
        $this->exportService->loadData($headers, $data, $options);

        $content = $this->exportService->getWriter(WriterType::XLSX)->stream();
        $strNow = (new DateTime())->format("YmdHis");
    
        return Response::respData([
            "filename" => "DILIGENCIAS_WEB_CORREO_{$labelExcel}.xlsx",
            "type" => "xlsx",
            "content" => $content,
        ]);
    }

    private function byCorreoWriter($ticketsArray)
    {
        $strNow = (new DateTime())->format("Ymd");
        $labelExcel = count($ticketsArray) > 1 ? $strNow : "TK".implode(',', $ticketsArray);
        $options = [
            "sheetIndex" => 0,
            'title' => $labelExcel,
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
            "customer_full_name" => ["label" => "CUSTOMER_FULL_NAME"],
            "nro_documento" => ["label" => "NRO_DOCUMENTO"],
            "correo" => ["label" => "CORREO"],
        ];
        $data = $this->extraccionRepo->getReporteDiligenciasWebCorreo($ticketsArray);
        if (count($data) === 0) {
            return Response::respError(["message" => "No hay afectados"]);
        }
        $this->exportService->loadData($headers, $data, $options);

        return Response::respData($this->exportService->getWriter(WriterType::XLSX));
    }

    public function byDocumento($tickets)
    {
        $ticketsArray = explode(",", str_replace(" ", "", $tickets));
        $strNow = (new DateTime())->format("Ymd");
        $labelExcel = count($ticketsArray) > 1 ? $strNow : "TK".$tickets;

        if (count($ticketsArray) > 100) {
            return Response::respError(["message" => "No se puede ingresar mas de 100 tickets"]);
        }

        $ticketsArray = $this->extraccionRepo->getTicketsWithDevolucionWeb($ticketsArray);
        if (count($ticketsArray) === 0) {
            return Response::respError(["message" => "El ticket no tiene usuarios afectados con devoluciones web"]);
        }

        $options = [
            "sheetIndex" => 0,
            'title' => $labelExcel,
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
            "customer_full_name" => ["label" => "CUSTOMER_FULL_NAME"],
            "tipo_documento" => ["label" => "TIPO_DOCUMENTO"],
            "nro_documento" => ["label" => "NRO_DOCUMENTO"],
            "mto_total_dev_igv" => ["label" => "MTO_TOTAL_DEV_IGV"],
        ];
        $data = $this->extraccionRepo->getReporteDiligenciasWebDocumento($ticketsArray);
        if (count($data) === 0) {
            return Response::respError(["message" => "No hay afectados"]);
        }
        $this->exportService->loadData($headers, $data, $options);

        $content = $this->exportService->getWriter(WriterType::XLSX)->stream();
        $strNow = (new DateTime())->format("YmdHis");
    
        return Response::respData([
            "filename" => "DILIGENCIAS_WEB_DOCUMENTO_{$labelExcel}.xlsx",
            "type" => "xlsx",
            "content" => $content,
        ]);
    }

    private function byDocumentoWriter($ticketsArray)
    {
        $strNow = (new DateTime())->format("Ymd");
        $labelExcel = count($ticketsArray) > 1 ? $strNow : "TK".implode(',', $ticketsArray);
        $options = [
            "sheetIndex" => 0,
            'title' => $labelExcel,
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
            "customer_full_name" => ["label" => "CUSTOMER_FULL_NAME"],
            "tipo_documento" => ["label" => "TIPO_DOCUMENTO"],
            "nro_documento" => ["label" => "NRO_DOCUMENTO"],
            "mto_total_dev_igv" => ["label" => "MTO_TOTAL_DEV_IGV"],
        ];
        $data = $this->extraccionRepo->getReporteDiligenciasWebDocumento($ticketsArray);
        if (count($data) === 0) {
            return Response::respError(["message" => "No hay afectados"]);
        }
        $this->exportService->loadData($headers, $data, $options);

        return Response::respData($this->exportService->getWriter(WriterType::XLSX));
    }

    public function byCorreoAndDocumento($strTickets)
    {
        $ticketsArray = explode(",", str_replace(" ", "", $strTickets));
        $strNow = (new DateTime())->format("Ymd");
        $labelExcel = count($ticketsArray) > 1 ? $strNow : "TK".$strTickets;

        if (count($ticketsArray) > 100) {
            return Response::respError(["message" => "No se puede ingresar mas de 100 tickets"]);
        }

        $ticketsArray = $this->extraccionRepo->getTicketsWithDevolucionWeb($ticketsArray);
        if (count($ticketsArray) === 0) {
            return Response::respError(["message" => "El ticket no tiene usuarios afectados con devoluciones web"]);
        }

        $responseCorreo = $this->byCorreoWriter($ticketsArray);
        $correoTempfilename = $responseCorreo->fails() ? null : $responseCorreo->data()->saveToTempfile();

        $this->exportService->reset();

        $responseDocumento = $this->byDocumentoWriter($ticketsArray);
        $documentoTempfilename = $responseDocumento->fails() ? null : $responseDocumento->data()->saveToTempfile();

        $zip = new ZipArchive();
        $zipFilename = "{$this->storage_path}/".Uuid::uuid4()->toString().".zip";
        $zip->open($zipFilename, ZipArchive::CREATE);
        if ($correoTempfilename !== null) {
            $zip->addFile($correoTempfilename, "DILIGENCIAS_WEB_CORREO_{$labelExcel}.xlsx");
        }
        if ($documentoTempfilename !== null) {
            $zip->addFile($documentoTempfilename, "DILIGENCIAS_WEB_DOCUMENTO_{$labelExcel}.xlsx");
        }
        $zip->close();

        $exportContent = file_get_contents($zipFilename);
        unlink($zipFilename);
        if ($correoTempfilename !== null) {
            unlink($correoTempfilename);
        }
        if ($documentoTempfilename !== null) {
            unlink($documentoTempfilename);
        }

        return Response::respData([
            "filename" => "DILIGENCIAS_WEB_{$labelExcel}.zip",
            "type" => "zip",
            "content" => $exportContent,
        ]);
    }
}
