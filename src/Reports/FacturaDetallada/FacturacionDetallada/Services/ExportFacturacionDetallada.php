<?php

namespace AMovil\Reports\FacturaDetallada\FacturacionDetallada\Services;

use AMovil\Reports\FacturaDetallada\FacturacionDetallada\Domain\FacturacionDetalladaRepository;
use AMovil\Reports\FacturaDetallada\FacturacionDetallada\Domain\TipoInputFacturacionDetallada;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportFacturacionDetallada
{
    private $repo;
    private $exportService;
    private $saveReportLog;

    public function __construct(
        FacturacionDetalladaRepository $repo,
        ExportService $exportService,
        SaveReportLog $saveReportLog
    ) {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke($value, $periodo)
    {
        $dtStart = new DateTime();
        try {
            $dtPeriodo = DateTime::createFromFormat("Ym", $periodo);
            $data = $this->getDataBy($value, $dtPeriodo);
            $tempfile = $this->export($data);
            $now = new DateTime();
            $content = file_get_contents($tempfile);
            $dtEnd = new DateTime();
            $this->reportLog($tempfile, $dtStart, $dtEnd);
            unlink($tempfile);
            return Response::respData([
                "filename" => "FACTURA_DETALLADA_".$now->format("YmdHis").".xlsx",
                "type" => "xlsx",
                "content" => $content
            ]);
        } catch (\Throwable $th) {
            $dtEnd = new DateTime();
            $this->reportLog(null, $dtStart, $dtEnd);
            throw $th;
        }
    }

    private function getDataBy($strValue, DateTime $periodo)
    {
        $strValue = str_replace(["\r", "\n", "\t"], ["", "", ""], $strValue);
        return $this->repo->getByNumCuentaAndPeriodo(explode(",", $strValue), $periodo);        
    }

    private function getDataFromExcel($excel)
    {
        $values = [];
        if ($excel !== null) {
            $reader = IOFactory::createReader('Xlsx');
            $spreedsheet = $reader->load($excel->getPathname());
            $sheet = $spreedsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            for ($i=1; $i <= $highestRow; $i++) {
                $values[] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
            }
        }
        return $values;
    }

    private function export($data)
    {
        
        $headers = [
            "nro_factura" => ["label" => "N°"],
            "nro_telefono" => ["label" => "Línea"],
            "plan" => ["label" => "Plan"],
            "total_cargo_mes_cigv" => ["label" => "Total Cargos por Línea (Incl. IGV)"],
            "igv" => ["label" => "IGV"],
            "total_cargo_mes_sigv" => ["label" => "Total Cargos por Línea (Sin IGV)"],
            "cargos_fijo_voz" => ["label" => "cargos fijos de voz"],
            "cargo_fijo_datos" => ["label" => "cargos fijos datos"],
            "cargos_adicionales_voz" => ["label" => "cargos adicionales voz"],
            "cargos_adicionales_datos" => ["label" => "cargos adicionales datos"],
            "cargo_ldi" => ["label" => "cargos ldi"],
            "cargo_roaming" => ["label" => "cargos roaming"],
            "cargo_por_equipos" => ["label" => "cargos por equipos"],
            "otros_cargo_abonos" => ["label" => "otros cargos y abonos"],
            "total_cobranzas_diferidas" => ["label" => "cargos diferidos (*)"],
            "cargo_ldn" => ["label" => "cargos ldn"],
            "cargo_vas " => ["label" => "cargos vas"],
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
            'title' => "FACTURA_DETALLADA",
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
        $filename = "FACTURA_DETALLADA_".$ini->format('YmdHis').".xlsx";
        $data = array_merge([
            'name' => 'FACTURA_DETALLADA',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => 'factura-detallada.facturacion-detallada',
            "filename" => $tempfile !== null ? $filename : null
        ], $extra_data);
        $this->saveReportLog->__invoke($data, $tempfile, 'FACTURA_DETALLADA');
    }
}
