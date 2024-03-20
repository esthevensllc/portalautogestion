<?php

namespace AMovil\Reports\CargosFijosServiciosActivos\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\CargosFijosServiciosActivos\Domain\DetalleRepository;
use AMovil\Reports\CargosFijosServiciosActivos\Domain\ReporteDetalle;
use AMovil\Reports\CargosFijosServiciosActivos\Domain\TipoReporte;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportReporte
{
    private $repo;
    private $exportService;
    private $saveReportLog;
    private $authService;
    private $storagePath;

    public function __construct(DetalleRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog, AuthService $authService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
        $this->authService = $authService;
        $this->storagePath = "/space/reportes/CARGOS_FIJOS_SERVICIOS_ACTIVOS";
    }

    public function __invoke($tipo_input, $lineas, $excel, $num_doc, $num_cuenta, $sn)
    {
        $dt_start = new DateTime();
        try {

            $excel_data = $this->getDataFromExcel($excel);

            $data = $this->repo->getReporte($tipo_input,explode(",", trim($lineas)), $excel_data, explode(",", trim($num_doc)), explode(",", trim($num_cuenta)), $sn);            
            
            return $data;
            
        } catch (\Throwable $th) {
            $dtEnd = new DateTime();
            $this->reportLog(null, $dt_start, $dtEnd);
            throw $th;
        }
    }

    private function export(&$data)
    {
        $this->exportService->reset();
        $headers = [
            "ruc" => ['label' => 'ruc'],
            "razon_social" => ['label' => 'razon_social'],
            "cuenta_larga" => ['label' => 'cuenta_larga'],
            "ciclo" => ['label' => 'ciclo'],
            "est_linea" => ['label' => 'est_linea'],
            "linea" => ['label' => 'linea'],
            "co_id" => ['label' => 'co_id'],
            "tmcode" => ['label' => 'tmcode'],
            "plan" => ['label' => 'plan'],
            "cr_total" => ['label' => 'cr_total'],
            "est_servicio" => ['label' => 'est_servicio'],
            "sncode" => ['label' => 'sncode'],
            "servicio" => ['label' => 'servicio'],
            "cf_notaproducto" => ['label' => 'cf_notaproducto'],
            "cf_modificado" => ['label' => 'cf_modificado']
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
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

        /*$columns_to_autosize = ['A','B','C','D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'];
        $sheet = $this->exportService->getExportReference()->getActiveSheet();
        foreach($columns_to_autosize as $col){
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }*/

        // $excel_content = $this->exportService->getWriter(WriterType::XLSX)->getOutput();

        // $this->exportService->reset();
        // $this->exportService->loadData($headers, $data, []);
        // $this->reportLog($tipo_reporte, $this->exportService, $dt_start, new DateTime());
        return $this->exportService->getWriter(WriterType::CSV);
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

    private function reportLog(?ExportService $exportService, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        $filename = "";
        $data = array_merge([
            'name' => 'CARGOS_FIJOS_SERVICIOS_ACTIVOS',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => 'CARGOS FIJOS Y SERVICIOS ACTIVOS',
        ], $extra_data);
        $this->saveReportLog->fromExport($exportService, $data, $filename, 'CARGOS_FIJOS_SERVICIOS_ACTIVOS');
    }
}
