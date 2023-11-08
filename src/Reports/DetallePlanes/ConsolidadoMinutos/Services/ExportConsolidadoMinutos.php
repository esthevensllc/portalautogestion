<?php

namespace AMovil\Reports\DetallePlanes\ConsolidadoMinutos\Services;

use AMovil\Reports\DetallePlanes\ConsolidadoMinutos\Domain\ConsolidadoMinutosRepository;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportConsolidadoMinutos
{
    private $repo;
    private $exportService;
    private $saveReportLog;

    public function __construct(
        ConsolidadoMinutosRepository $repo,
        ExportService $exportService,
        SaveReportLog $saveReportLog
    ) {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke($numCuenta, $periodo)
    {
        $dtStart = new DateTime();
        try {
            $dtPeriodo = DateTime::createFromFormat("Ym", $periodo);
            $data = $this->repo->getReporteByNumCuentaAndPeriodo($numCuenta, $dtPeriodo);
            $tempfile = $this->export($data);
            $now = new DateTime();
            $content = file_get_contents($tempfile);
            $dtEnd = new DateTime();
            $this->reportLog($tempfile, $dtStart, $dtEnd);
            unlink($tempfile);
            return Response::respData([
                "filename" => "CONSOLIDADO_MINUTOS_".$now->format("YmdHis").".xlsx",
                "type" => "xlsx",
                "content" => $content
            ]);
        } catch (\Throwable $th) {
            $dtEnd = new DateTime();
            $this->reportLog(null, $dtStart, $dtEnd);
            throw $th;
        }
    }

    private function export($data)
    {
        $headers = [
            "cuenta" => ['label' => 'CUENTA'],
            "fecha_inicio_fecha_fin" => ['label' => 'FECHA_INICIO_FECHA_FIN'],
            "nro_telefono" => ['label' => 'NRO_TELEFONO'],
            "cantidad_lineas" => ['label' => 'CANTIDAD_LINEAS'],
            "cantidad_planes" => ['label' => 'CANTIDAD_PLANES'],
            "plan" => ['label' => 'PLAN'],
            "datos_gb" => ['label' => 'DATOS_GB'],
            "sms" => ['label' => 'SMS'],
            "voz" => ['label' => 'VOZ'],
            "hacia_claro" => ['label' => 'HACIA_CLARO'],
            "rpc" => ['label' => 'RPC'],
            "movil_otros_operadores" => ['label' => 'MOVIL_OTROS_OPERADORES'],
            "hacia_fijos" => ['label' => 'HACIA_FIJOS'],
            "ldn" => ['label' => 'LDN'],
            "ldi" => ['label' => 'LDI'],
            "sin_frontera_datos" => ['label' => 'SIN_FRONTERA_DATOS'],
            "sin_frontera_voz" => ['label' => 'SIN_FRONTERA_VOZ'],
            "sms_sin_frontera" => ['label' => 'SMS_SIN_FRONTERA'],
            "roaming_datos" => ['label' => 'ROAMING_DATOS'],
            "roaming_sms" => ['label' => 'ROAMING_SMS'],
            "duracion_roaming_voz" => ['label' => 'DURACION_ROAMING_VOZ'],
            "roaming_voz" => ['label' => 'ROAMING_VOZ'],
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
            'title' => "CONSOLIDADO MINUTOS",
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
        return $this->exportService->getWriter(WriterType::XLSX)->saveToTempfile();
    }

    private function reportLog($tempfile, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        $filename = "CONSOLIDADO_MINUTOS_".$ini->format('YmdHis').".xlsx";
        $data = array_merge([
            'name' => 'DETALLE_PLANES',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => 'detalle-planes.consolidado-minutos',
            "filename" => $tempfile !== null ? $filename : null
        ], $extra_data);
        $this->saveReportLog->__invoke($data, $tempfile, 'DETALLE_PLANES');
    }
}
