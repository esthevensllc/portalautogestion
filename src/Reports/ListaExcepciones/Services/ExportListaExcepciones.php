<?php

namespace AMovil\Reports\ListaExcepciones\Services;

use AMovil\Reports\ListaExcepciones\Domain\ListaExcepcionesRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportListaExcepciones
{
    private $repo;
    private $exportService;

    public function __construct(ListaExcepcionesRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($imei)
    {
        $results = [];
        if($imei === null){
            $results = $this->repo->findAll();
        }else{
            $results = $this->repo->getByImei($imei);
        }
        $content = $this->export($results);
        $strDate = (new DateTime())->format("Ymd");
        return Response::respData([
            "filename" => "Lista_Excepciones_{$strDate}.xlsx",
            "content" => $content,
        ]);
    }

    private function export($data)
    {
        $headers = [
            "numero_registro" => ["label" => "NUMERO_REGISTRO"],
            "codigo_cliente" => ["label" => "CODIGO_CLIENTE"],
            "cargo" => ["label" => "CARGO"],
            "area" => ["label" => "AREA"],
            "direccion" => ["label" => "DIRECCION"],
            "jefe" => ["label" => "JEFE"],
            "titular" => ["label" => "TITULAR"],
            "tipo_documento" => ["label" => "TIPO_DOCUMENTO"],
            "numero_documento" => ["label" => "NUMERO_DOCUMENTO"],
            "linea" => ["label" => "LINEA"],
            "imei" => ["label" => "IMEI"],
            "imsi" => ["label" => "IMSI"],
            "estado" => ["label" => "ESTADO"],
            "ejecucion_bloqueo" => ["label" => "EJECUCION_BLOQUEO"],
            "fecha_registro" => ["label" => "FECHA_REGISTRO"],
            "trama" => ["label" => "TRAMA"],
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
            'title' => "OLT_CMTS",
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
        return $this->exportService->getWriter(WriterType::XLSX)->getOutput();
    }
}
