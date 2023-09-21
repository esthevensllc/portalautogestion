<?php

namespace AMovil\Reports\General\BloqueoImei\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\General\BloqueoImei\Domain\BloqueoImeiRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportBloqueoImei
{
    private $repo;
    private $exportService;
    private $authService;

    public function __construct(BloqueoImeiRepository $repo, ExportService $exportService, AuthService $authService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->authService = $authService;
    }

    public function __invoke($file, $fechaIni, $fechaFin): Response
    {
        $dtFechaIni = DateTime::createFromFormat("d/m/Y H:i:s", $fechaIni);
        $dtFechaFin = DateTime::createFromFormat("d/m/Y H:i:s", $fechaFin);
        $filename = $file->getClientOriginalName();

        if(!$this->validateFilename($filename)){
            throw new Exception("El nombre del archivo no es valido");
        }

        if($dtFechaIni->format("Ymd") !== $dtFechaFin->format("Ymd")){
            throw new Exception("No se puede consultar un rango de fechas con dias diferentes");
        }

        $reportLog = $this->repo->getReporteLogByCriteria([
            ["filename", $filename],
            ["delete_flag", 0]
        ]);
        if(count($reportLog) > 0){
            throw new Exception("No se puede procesar el mismo archivo nuevamente");
        }

        $inputFile = $this->getDataFromFile($file);

        $id = (new DateTime())->format("YmdHis");
        $data = $this->repo->getReporte($id, $inputFile["filename"], $inputFile["values"], $dtFechaIni, $dtFechaFin);
        // dd($data);

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
            "pgw_address" => ['label' => 'pgw_address'],
            "charging_id" => ['label' => 'charging_id'],
            "served_pdppdn_address" => ['label' => 'served_pdppdn_address'],
            "duration" => ['label' => 'duration'],
            "charging_characteristics" => ['label' => 'charging_characteristics'],
            "losd_rating_group" => ['label' => 'losd_rating_group'],
            "uli_lac" => ['label' => 'uli_lac'],
            "uli_sac" => ['label' => 'uli_sac'],
            "uli_ci" => ['label' => 'uli_ci'],
            "uli_tai" => ['label' => 'uli_tai'],
            "uli_ecgi" => ['label' => 'uli_ecgi'],
            "losd_time_of_report" => ['label' => 'losd_time_of_report'],
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
            'title' => "BASE_BLOQUEO_IMEI",
            'rowType' => 'array',
            // 'y_start_index' => 0,
            // 'x_start_index' => 0,
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

        $this->repo->saveReporteLog($id, $this->authService->getUserIdentifier(), $filename, count($inputFile["values"]));

        return new Response([], [
            "filename" => "BASE_BLOQUEO_IMEI_{$id}.xlsx",
            "type" => "xlsx",
            "content" => $content
        ]);
    }

    private function getDataFromFile($file)
    {
        $values = [];
        if ($file !== null) {
            $reader = IOFactory::createReader('Csv');
            $spreedsheet = $reader->load($file->getPathname());
            $sheet = $spreedsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            for ($i=1; $i <= $highestRow; $i++) {
                $values[] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
            }
        }
        return ["filename" => $file->getClientOriginalName(), "values" => $values];
    }

    private function validateFilename($filename){
        return preg_match('/^[A-Za-z0-9_\.]+$/', $filename) === 1;
    }
}
