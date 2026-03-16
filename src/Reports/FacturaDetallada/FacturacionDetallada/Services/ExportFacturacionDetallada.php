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
            $tempfile = $this->export($data, $dtPeriodo, $value);
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

    private function export($data, $periodoInput, $nroCuenta)
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
            "cargo_vas" => ["label" => "cargos vas"],
        ];

        $firstRow = $data[0] ?? null;

        $nroRecibo = '';

        if (is_array($firstRow)) {
            $nroRecibo = $firstRow['nro_factura'] ?? '';
        } elseif (is_object($firstRow)) {
            $nroRecibo = $firstRow->nro_factura ?? '';
        }

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
            'title' => "FACTURA_DETALLADA",
            'y_start_index' => 8, // encabezado en fila 9, data desde fila 10
            'styles' => [
                'A2:Q2' => [
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                ],
                'A4:B6' => [
                    'font' => [
                        'size' => 10,
                    ],
                ],
                'header' => [
                    'font' => [
                        'bold' => true,
                        'size' => 9
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => SpreadsheetStyle\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ],
                'body' => [
                    'font' => [
                        'size' => 9
                    ],
                ]
            ]
        ]);

        $periodoExcel = $periodoInput instanceof \DateTimeInterface
            ? $periodoInput->format('Ym')
            : date('Ym', strtotime((string)$periodoInput));

        $sheet = $this->exportService->getExportReference()->getActiveSheet();

        // bloque superior
        $sheet->setCellValue('A2', 'FACTURA DETALLADA');
        $sheet->setCellValue('A4', 'Periodo:');
        $sheet->setCellValueExplicit(
            'B4',
            $periodoExcel,
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
        );
        $sheet->setCellValue('A5', 'Nro. Cuenta:');
        $sheet->setCellValue('B5', $nroCuenta);
        $sheet->setCellValue('A6', 'Nro. Recibo:');
        $sheet->setCellValue('B6', $nroRecibo);

        // estilos bloque superior
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->getStyle('A4:A6')->getFont()->setBold(true);
        $sheet->getStyle('A2')->getFont()->setSize(12);

        // ajustar ancho de columnas según el formato del excel correcto
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(22.71);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(22);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(13);
        $sheet->getColumnDimension('I')->setWidth(20);
        $sheet->getColumnDimension('J')->setWidth(13);
        $sheet->getColumnDimension('K')->setWidth(12);
        $sheet->getColumnDimension('L')->setWidth(15);
        $sheet->getColumnDimension('M')->setWidth(18);
        $sheet->getColumnDimension('N')->setWidth(20);
        $sheet->getColumnDimension('O')->setWidth(18);
        $sheet->getColumnDimension('P')->setWidth(12);
        $sheet->getColumnDimension('Q')->setWidth(13);

        // wrap del encabezado
        $sheet->getStyle('A9:Q9')->getAlignment()->setWrapText(true);

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
