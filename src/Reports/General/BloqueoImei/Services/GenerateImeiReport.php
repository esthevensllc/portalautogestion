<?php

namespace AMovil\Reports\General\BloqueoImei\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\General\BloqueoImei\Domain\BloqueoImeiRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class GenerateImeiReport
{
    private $repo;
    private $exportService;
    private $storageService;
    private $storage;
    private $baseStoragePath = "/space/reportes/imei_cdr_automatico";

    public function __construct(BloqueoImeiRepository $repo, ExportService $exportService, StorageService $storageService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->storageService = $storageService;
        $this->storage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
    }

    public function __invoke($fechaIni, $fechaFin): Response
    {
        $dtFechaIni = DateTime::createFromFormat("d/m/Y H:i:s", $fechaIni);
        $dtFechaFin = DateTime::createFromFormat("d/m/Y H:i:s", $fechaFin);

        if($dtFechaIni->format("Ymd") !== $dtFechaFin->format("Ymd")){
            throw new Exception("No se puede consultar un rango de fechas con dias diferentes");
        }

        $config = $this->repo->getConfig();
        if($config === null){
            throw new Exception("No existe una configuracion para imei cdr");
        }
        if((int) $config["generate_report"] === 0){
            return Response::respData(["message" => "La generación de reportes esta desactivada"]);
        }

        $data = $this->repo->getReporteFromBaseImei($dtFechaIni, $dtFechaFin);

        $headers = [
            "served_imeisv2" => ['label' => 'served_imeisv2'],
            "served_msisdn" => ['label' => 'served_msisdn'],
            "serving_node_address1" => ['label' => 'serving_node_address1'],
            "serving_node_plmn_identifier" => ['label' => 'serving_node_plmn_identifier'],
            "name" => ['label' => 'name'],
            "record_opening_time" => ['label' => 'record_opening_time'],
            "rattype" => ['label' => 'rattype'],
            "cause_for_rec_closing" => ['label' => 'cause_for_rec_closing'],
            "losd_datavolume_fbc_uplink" => ['label' => 'losd_datavolume_fbc_uplink'],
            "losd_datavolume_fbc_downlink" => ['label' => 'losd_datavolume_fbc_downlink'],
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
            'title' => "Red_Bloqueo_imeis",
            'rowType' => 'array',
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
        $content = $this->exportService->getWriter(WriterType::XLSX)->getOutput();

        $subDirectory = $dtFechaIni->format("Ym");
        $strTime = $dtFechaIni->format("YmdH")."00";
        $filename = "Red_Bloqueo_imeis{$strTime}.xlsx";
        $this->storage->put("{$this->baseStoragePath}/{$subDirectory}/{$filename}", $content);

        $sizeBytes = $this->storage->size("{$this->baseStoragePath}/{$subDirectory}/{$filename}");

        $this->repo->saveAutomaticReport($strTime, $filename, count($data), $sizeBytes, $dtFechaIni);

        return new Response([], [
            "filename" => $filename,
            "type" => "xlsx",
            "path" => "{$this->baseStoragePath}/{$subDirectory}"
        ]);
    }

    public function __invokeOnline($fechaIni, $fechaFin): Response
    {
        $dtFechaIni = DateTime::createFromFormat("d/m/Y H:i:s", $fechaIni);
        $dtFechaFin = DateTime::createFromFormat("d/m/Y H:i:s", $fechaFin);

        if($dtFechaIni->format("Ymd") !== $dtFechaFin->format("Ymd")){
            throw new Exception("No se puede consultar un rango de fechas con dias diferentes");
        }

        $config = $this->repo->getConfig();
        if($config === null){
            throw new Exception("No existe una configuracion para imei cdr");
        }
        if((int) $config["generate_report"] === 0){
            return Response::respData(["message" => "La generación de reportes esta desactivada"]);
        }

        $data = $this->repo->getReporteFromBaseImeiOnline($dtFechaIni, $dtFechaFin);

        $headers = [
            "served_imeisv2" => ['label' => 'served_imeisv2'],
            "served_msisdn" => ['label' => 'served_msisdn'],
            "serving_node_address1" => ['label' => 'serving_node_address1'],
            "serving_node_plmn_identifier" => ['label' => 'serving_node_plmn_identifier'],
            "name" => ['label' => 'name'],
            "record_opening_time" => ['label' => 'record_opening_time'],
            "rattype" => ['label' => 'rattype'],
            "cause_for_rec_closing" => ['label' => 'cause_for_rec_closing'],
            "losd_datavolume_fbc_uplink" => ['label' => 'losd_datavolume_fbc_uplink'],
            "losd_datavolume_fbc_downlink" => ['label' => 'losd_datavolume_fbc_downlink'],
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
            'title' => "Red_Bloqueo_imeis",
            'rowType' => 'array',
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
        $content = $this->exportService->getWriter(WriterType::XLSX)->getOutput();

        $subDirectory = $dtFechaIni->format("Ym");
        $strTime = $dtFechaIni->format("YmdH")."00";
        $filename = "Red_Bloqueo_imeis_online{$strTime}.xlsx";
        $this->storage->put("{$this->baseStoragePath}/{$subDirectory}/{$filename}", $content);

        $sizeBytes = $this->storage->size("{$this->baseStoragePath}/{$subDirectory}/{$filename}");

        $this->repo->saveAutomaticReportOnline($strTime, $filename, count($data), $sizeBytes, $dtFechaIni);

        return new Response([], [
            "filename" => $filename,
            "type" => "xlsx",
            "path" => "{$this->baseStoragePath}/{$subDirectory}"
        ]);
    }

}
