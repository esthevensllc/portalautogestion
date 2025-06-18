<?php

namespace AMovil\Reports\RepRecargas\Services;

use AMovil\Reports\ReportLog\Domain\ReportLogStatus;
use AMovil\Reports\ReportLog\Services\SaveReportDto;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Reports\RepRecargas\Domain\ReporteRecargasRepository;
use AMovil\Reports\RepRecargas\Domain\TipoReporte;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportDetalleRecargas
{
    private $repo;
    private $exportService;
    private $saveReportLog;
    public function __construct(ReporteRecargasRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke($tipo_reporte, ?string $lineas, ?string $fecha1, ?string $fecha2): Response
    {
        $fechaIniExec = new DateTime();
        
        $lineas = str_replace(" ", "",$lineas);
        $array_lineas = explode(",", $lineas);
        $dt_fecha1 = DateTime::createFromFormat("Y-m-d", $fecha1);
        $dt_fecha2 = DateTime::createFromFormat("Y-m-d", $fecha2);
        $reporteInput = ['tipo_reporte' => $tipo_reporte, 'lineas' => $array_lineas, 'fechaInicio' => $fecha1, 'fechaFin' => $fecha2];

        try {
            $data = [];
            $headers = [];
            $options = [
                'sheetIndex' => 0,
                'title' => "",
                // 'y_start_index' => 0,
                // 'x_start_index' => 0,
                'styles' => [
                    'header' => [
                        'font' => ['bold' => true, 'size' => 9],
                        /*'borders'=> [
                            'allBorders' => ['borderStyle' => SpreadsheetStyle\Border::BORDER_THIN, 'color' => array('rgb'=>'000000')]
                        ]*/
                    ],
                    'body' => [
                        'font' => ['size' => 9],
                    ]
                ]
            ];
            $numberFormat = ['numberFormat' => ['formatCode' => SpreadsheetStyle\NumberFormat::FORMAT_NUMBER_00]];
            switch ($tipo_reporte) {
                case '01':
                    $tipo_reporte = TipoReporte::detalle();
                    $data = $this->repo->getDetalle($array_lineas, $dt_fecha1, $dt_fecha2);
                    $headers = [
                        "rechargedate" => ["label" => "RECHARGEDATE"],
                        "msisdn" => ["label" => "MSISDN"],
                        "proid" => ["label" => "PROID"],
                        "productid" => ["label" => "PRODUCTID"],
                        "acc_charge_general" => ["label" => "ACC_CHARGE_GENERAL", 'bodyStyles' => $numberFormat],
                        "numberrechargegpa" => ["label" => "NUMBERRECHARGEGPA", 'bodyStyles' => $numberFormat],
                    ];
                    break;
                case '02':
                    $tipo_reporte = TipoReporte::extras();
                    $data = $this->repo->getExtras($array_lineas, $dt_fecha1, $dt_fecha2);
                    $headers = [
                        "rechargedate" => ["label" => "RECHARGEDATE"],
                        "msisdn" => ["label" => "MSISDN"],
                        "proid" => ["label" => "PROID"],
                        "productid" => ["label" => "PRODUCTID"],
                        "acc_charge_general" => ["label" => "ACC_CHARGE_GENERAL", 'bodyStyles' => $numberFormat],
                        "numberrechargegpa" => ["label" => "NUMBERRECHARGEGPA", 'bodyStyles' => $numberFormat],
                        "bonorecargavoz_charge" => ["label" => "BONORECARGAVOZ_CHARGE", 'bodyStyles' => $numberFormat],
                        "bonorecargavoz_number_charge" => ["label" => "BONORECARGAVOZ_NUMBER_CHARGE", 'bodyStyles' => $numberFormat],
                        "bonorecargasms_charge" => ["label" => "BONORECARGASMS_CHARGE", 'bodyStyles' => $numberFormat],
                        "bonorecargasms_number_charge" => ["label" => "BONORECARGASMS_NUMBER_CHARGE", 'bodyStyles' => $numberFormat],
                        "paqupromoc_charge" => ["label" => "PAQUPROMOC_CHARGE", 'bodyStyles' => $numberFormat],
                        "paqupromoc_number_charge" => ["label" => "PAQUPROMOC_NUMBER_CHARGE", 'bodyStyles' => $numberFormat],
                        "paquprosms_charge" => ["label" => "PAQUPROSMS_CHARGE", 'bodyStyles' => $numberFormat],
                        "paquprosms_number_charge" => ["label" => "PAQUPROSMS_NUMBER_CHARGE", 'bodyStyles' => $numberFormat],
                        "paqurecumoc_charge" => ["label" => "PAQURECUMOC_CHARGE", 'bodyStyles' => $numberFormat],
                        "paqurecumoc_number_charge" => ["label" => "PAQURECUMOC_NUMBER_CHARGE", 'bodyStyles' => $numberFormat],
                        "creation_date" => ["label" => "CREATION_DATE"],
                    ];
                    break;
                default:
                    # code...
                    break;
            }

            $options["title"] = $tipo_reporte->getName();

            $this->exportService->loadData($headers, $data, $options);
            $sheet = $this->exportService->getExportReference()->getActiveSheet();
            $columns_to_autosize = ['A','B','C','D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q'];
            foreach($columns_to_autosize as $col){
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = $tipo_reporte->getName()."_".$fechaIniExec->format('YmdHis').".xlsx";
            $filepath = SaveReportLog::LOCAL_PATH."/REPORTE_RECARGAS/{$filename}";
            $this->exportService->getWriter(WriterType::XLSX)->save($filepath);

            $this->reportLog($tipo_reporte, $filepath, $filename, $fechaIniExec, new DateTime(), $reporteInput, null);

            $content = file_get_contents($filepath);

            return new Response([], [
                "type" => "xlsx",
                "content" => $content,
                "filename" => $tipo_reporte->getName()."_".implode("_", $array_lineas).".xlsx"
            ]);   
        } catch (\Throwable $th) {
            $this->reportLog($tipo_reporte, null, null, $fechaIniExec, new DateTime(), $reporteInput, $th);
            throw $th;
        }
    }

    private function reportLog(TipoReporte $tipo_reporte, ?string $local_file, ?string $filename, DateTime $ini, DateTime $fin, array $input, ?\Throwable $error)
    {
        $logDto = SaveReportDto::create(
            'REPORTE_RECARGAS',
            $filename,
            $ini,
            $fin,
            $error === null ? ReportLogStatus::CORRECTO : ReportLogStatus::ERROR,
            $error !== null ? $error->getMessage() : null,
            $tipo_reporte->getTracName(),
            json_encode($input)
        );
        $this->saveReportLog->create($logDto);
        if ($local_file !== null) {
            $this->saveReportLog->sendFileToRemoteServer($local_file, "REPORTE_RECARGAS/{$filename}");
        }
    }
}
