<?php

namespace AMovil\Reports\DetallePlanes\FacturacionDetallada\Services;

use AMovil\Reports\DetallePlanes\FacturacionDetallada\Domain\FacturacionDetalladaRepository;
use AMovil\Reports\DetallePlanes\FacturacionDetallada\Domain\TipoInputFacturacionDetallada;
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

    public function __invoke($tipoInputId, $file, $value, $periodo)
    {
        $dtStart = new DateTime();
        try {
            $dtPeriodo = DateTime::createFromFormat("Ym", $periodo);
            $data = $this->getDataBy($tipoInputId, $file, $value, $dtPeriodo);
            $tempfile = $this->export($data);
            $now = new DateTime();
            $content = file_get_contents($tempfile);
            $dtEnd = new DateTime();
            $this->reportLog($tempfile, $dtStart, $dtEnd);
            unlink($tempfile);
            return Response::respData([
                "filename" => "FACTURACION_DETALLADA_".$now->format("YmdHis").".xlsx",
                "type" => "xlsx",
                "content" => $content
            ]);
        } catch (\Throwable $th) {
            $dtEnd = new DateTime();
            $this->reportLog(null, $dtStart, $dtEnd);
            throw $th;
        }
    }

    private function getDataBy(int $tipoInputId, $file, $strValue, DateTime $periodo){
        switch ($tipoInputId) {
            case TipoInputFacturacionDetallada::NUMERO_CUENTA:
                $strValue = str_replace(["\r", "\n", "\t"], ["", "", ""], $strValue);
                return $this->repo->getByNumCuentaAndPeriodo(explode(",", $strValue), $periodo);
            case TipoInputFacturacionDetallada::EXCEL:
                $values = $this->getDataFromExcel($file);
                return $this->repo->getByNumCuentaAndPeriodo($values, $periodo);
            default:
                throw new Exception("Tipo de input no valido");
                break;
        }
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
            "nro_factura" => ["label" => "NRO_FACTURA"],
            "cuenta" => ["label" => "CUENTA"],
            "fecha_inicio_fecha_fin" => ["label" => "FECHA_INICIO_FECHA_FIN"],
            "nro_telefono" => ["label" => "NRO_TELEFONO"],
            "cantidad_lineas" => ["label" => "CANTIDAD_LINEAS"],
            "cantidad_planes" => ["label" => "CANTIDAD_PLANES"],
            "plan" => ["label" => "PLAN"],
            "total_cargo_mes" => ["label" => "TOTAL_CARGO_MES"],
            "cargos_fijo_voz" => ["label" => "CARGOS_FIJO_VOZ"],
            "cargo_fijo_datos" => ["label" => "CARGO_FIJO_DATOS"],
            "cargos_adicionales_voz" => ["label" => "CARGOS_ADICIONALES_VOZ"],
            "cargos_adicionales_datos" => ["label" => "CARGOS_ADICIONALES_DATOS"],
            "cargo_ldi" => ["label" => "CARGO_LDI"],
            "cargo_roaming" => ["label" => "CARGO_ROAMING"],
            "cargo_por_equipos" => ["label" => "CARGO_POR_EQUIPOS"],
            "otros_cargo_abonos" => ["label" => "OTROS_CARGO_ABONOS"],
            "total_cargo_mes_sigv" => ["label" => "TOTAL_CARGO_MES_SIGV"],
            "igv" => ["label" => "IGV"],
            "total_cargo_mes_cigv" => ["label" => "TOTAL_CARGO_MES_CIGV"],
            "t_otros_cargos_nafect_igv" => ["label" => "T_OTROS_CARGOS_NAFECT_IGV"],
            "total_cobranzas_diferidas" => ["label" => "TOTAL_COBRANZAS_DIFERIDAS"],
            "cargo_vas" => ["label" => "CARGO_VAS"],
            "cargo_ldn" => ["label" => "CARGO_LDN"],
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
            'title' => "FACTURACION_DETALLADA",
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
        $filename = "FACTURACION_DETALLADA_".$ini->format('YmdHis').".xlsx";
        $data = array_merge([
            'name' => 'DETALLE_PLANES',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => 'detalle-planes.facturacion-detallada',
            "filename" => $tempfile !== null ? $filename : null
        ], $extra_data);
        $this->saveReportLog->__invoke($data, $tempfile, 'DETALLE_PLANES');
    }
}
