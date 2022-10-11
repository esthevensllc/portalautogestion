<?php

namespace AMovil\Reports\RepFiscalia\Services;

use AMovil\Reports\RepFiscalia\Domain\ReporteFiscalRepository;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;
use DateTime;
use Exception;

class ExportReporteFiscal
{
    private $repo;
    private $exportService;
    private $saveReportLog;

    public function __construct(ReporteFiscalRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke($msisdn, $periodo1, $periodo2)
    {
        $dt_start = new DateTime();
        try {
            $dt_periodo1 = DateTime::createFromFormat('Y-m-d', $periodo1);
            $dt_periodo2 = DateTime::createFromFormat('Y-m-d', $periodo2);
            if(!$dt_periodo1 || !$dt_periodo2){
                throw new Exception("Los periodos no son validos");
            }
            $data = $this->repo->getReporteByMsisdn_Periodos($msisdn, $dt_periodo1, $dt_periodo2);

            $headers = [
                "modalidad" => ['label' => 'MODALIDAD'],
                "tipo_llamada" => ['label' => 'TIPO_LLAMADA'],
                "numero_origen" => ['label' => 'NUMERO_ORIGEN'],
                "numero_destino" => ['label' => 'NUMBERO_DESTINO'],
                "fecha_hora" => ['label' => 'FECHA_HORA'],
                "minutos" => ['label' => 'MINUTOS'],
                "segundos" => ['label' => 'SEGUNDOS'],
                "lac" => ['label' => 'LAC'],
                "celda" => ['label' => 'CELDA'],
                "ubicacion_a" => ['label' => 'UBICACION_A'],
                "provincia" => ['label' => 'PROVINCIA'],
                "distrito" => ['label' => 'DISTRITO'],
                "departamento" => ['label' => 'DEPARTAMENTO'],
                "imei_a" => ['label' => 'IMEI_A'],
                "imei_b" => ['label' => 'IMEI_B'],
            ];

            $options = [
                'sheetIndex' => 0,
                'title' => "{$periodo1} - {$periodo2}",
                // 'y_start_index' => 0,
                // 'x_start_index' => 0,
                'fillWith' => '-',
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
            ];

            $this->exportService->loadData($headers, $data, $options);

            $excel_content = $this->exportService->getWriter(WriterType::XLSX)->getOutput();

            $this->exportService->reset();
            $this->exportService->loadData($headers, $data, []);
            $this->reportLog($this->exportService, $dt_start, new DateTime());

            return $excel_content;
        } catch (\Throwable $th) {
            $this->reportLog(null, $dt_start, new DateTime());
            throw $th;
        }
    }

    private function reportLog(?ExportService $exportService, DateTime $ini, DateTime $fin)
    {
        $filename = "REPORTE_FISCAL_".$ini->format('YmdHis');
        $this->saveReportLog->fromExport($exportService, [
            'name' => 'REPORTE_FISCAL',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
        ],
        $filename, 'REPORTE_FISCAL');
    }
}
