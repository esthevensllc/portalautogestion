<?php

namespace AMovil\Reports\RepDetLlamadas\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\RepDetLlamadas\Domain\DetalleLlamadasRepository;
use AMovil\Reports\RepDetLlamadas\Domain\ReporteDetalleLlamada;
use AMovil\Reports\RepDetLlamadas\Domain\TipoReporte;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportReporteLlamadas
{
    private $repo;
    private $exportService;
    private $saveReportLog;
    private $authService;
    private $storagePath;

    public function __construct(DetalleLlamadasRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog, AuthService $authService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
        $this->authService = $authService;
        $this->storagePath = "/space/www/html/portalautogestion_rel_llamadas";
    }

    public function __invoke($tipo_reporte, $periodo1, $periodo2, $tipo_input, $lineas, $excel, $num_doc, $num_cuenta, $cod_cliente, $numeros_primarios)
    {
        $dt_start = new DateTime();
        try {
            $dt_periodo1 = DateTime::createFromFormat('Y-m-d', $periodo1);
            $dt_periodo2 = DateTime::createFromFormat('Y-m-d', $periodo2);

            if(!$dt_periodo1 || !$dt_periodo2){
                throw new Exception("Los periodos no son validos");
            }

            if(!TipoReporte::isValid($tipo_reporte)){
                throw new Exception("El tipo de reporte no es valido");
            }

            $excel_data = $this->getDataFromExcel($excel);
            // dd($excel_data);
            $data = [];
            switch ($tipo_input) {
                case 'tab_lineas':
                    $data = $this->repo->getReporteByPeriodo_Lineas_tipo($dt_periodo1, $dt_periodo2, explode(",", trim($lineas)), $tipo_reporte);
                    break;
                case 'tab_excel':
                    $data = $this->repo->getReporteByPeriodo_Lineas_tipo($dt_periodo1, $dt_periodo2, $excel_data, $tipo_reporte);
                    break;
                case 'tab_numero_documento':
                    $data = $this->repo->getReporteByPeriodo_NumDocumento_tipo($dt_periodo1, $dt_periodo2, [$num_doc], $tipo_reporte);
                    break;
                case 'tab_numero_cuenta':
                    $data = $this->repo->getReporteByPeriodo_NumCuenta_tipo($dt_periodo1, $dt_periodo2, [$num_cuenta], $tipo_reporte);
                    break;
                case 'tab_cod_cliente':
                    $data = $this->repo->getReporteByPeriodo_CodCliente_tipo($dt_periodo1, $dt_periodo2, [$cod_cliente], $tipo_reporte);
                    break;
                case 'tab_numeros_primarios':
                    $data = $this->repo->getReporteByPeriodo_Lineas_tipo($dt_periodo1, $dt_periodo2, explode(",", trim($numeros_primarios)), $tipo_reporte, true);
                    break;
                default:
                    throw new Exception("Input no valido");
                    break;
            }

            if($data->getCount() > ReporteDetalleLlamada::LIMIT){
                $userIdentifier = $this->authService->getUserIdentifier();
                $now = (new DateTime())->format("YmdH");
                foreach ($data->getIterator() as $index => $chunk) {
                    $filename = "Detalle_llamadas_{$userIdentifier}_{$now}_par".($index+1).".xlsx";
                    $this->export($chunk, "{$periodo1} - {$periodo2}")->save("{$this->storagePath}/{$filename}");
                    $this->repo->saveLogReporteTemp($filename, new DateTime(), filesize("{$this->storagePath}/{$filename}"));
                }
                return [
                    "message" => "El archivo supera el limite de registros se enviara los reportes al modulo de reportes y estaran disponibles solo por el resto del dia"
                ];
            }else{
                foreach ($data->getIterator() as $chunk) {
                    $excel_content = $this->export($chunk, "{$periodo1} - {$periodo2}")->getOutput();

                    $this->exportService->reset();
                    $headers = [
                        "numero_origen" => ['label' => 'NUMERO_ORIGEN'],
                        "fecha" => ['label' => 'FECHA'],
                        "hora_inicio" => ['label' => 'HORA_INICIO'],
                        "hora_fin" => ['label' => 'HORA_FIN'],
                        "numero_destino" => ['label' => 'NUMBERO_DESTINO'],
                        "consumo" => ['label' => 'CONSUMO'],
                        "tipo" => ['label' => 'TIPO'],
                    ];
                    $this->exportService->loadData($headers, $chunk, []);
                    $this->reportLog($tipo_reporte, $this->exportService, $dt_start, new DateTime());
                    return $excel_content;
                }
            }
        } catch (\Throwable $th) {
            $this->reportLog($tipo_reporte, null, $dt_start, new DateTime(), ['mensaje' => $th->getMessage()]);
            throw $th;
        }
    }

    private function export(&$data, $title)
    {
        $this->exportService->reset();
        $headers = [
            "numero_origen" => ['label' => 'NUMERO_ORIGEN'],
            "fecha" => ['label' => 'FECHA'],
            "hora_inicio" => ['label' => 'HORA_INICIO'],
            "hora_fin" => ['label' => 'HORA_FIN'],
            "numero_destino" => ['label' => 'NUMBERO_DESTINO'],
            "consumo" => ['label' => 'CONSUMO'],
            "tipo" => ['label' => 'TIPO'],
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
            'title' => $title,
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

        $columns_to_autosize = ['A','B','C','D', 'E', 'F', 'G'];
        $sheet = $this->exportService->getExportReference()->getActiveSheet();
        foreach($columns_to_autosize as $col){
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // $excel_content = $this->exportService->getWriter(WriterType::XLSX)->getOutput();

        // $this->exportService->reset();
        // $this->exportService->loadData($headers, $data, []);
        // $this->reportLog($tipo_reporte, $this->exportService, $dt_start, new DateTime());
        return $this->exportService->getWriter(WriterType::XLSX);
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

    private function reportLog($tipo_reporte, ?ExportService $exportService, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        $inicial = TipoReporte::getLabel($tipo_reporte);
        $filename = "{$inicial}_".$ini->format('YmdHis');
        $data = array_merge([
            'name' => 'DETALLE_LLAMADAS',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => TipoReporte::getName($tipo_reporte),
        ], $extra_data);
        $this->saveReportLog->fromExport($exportService, $data, $filename, 'DETALLE_LLAMADAS');
    }
}
