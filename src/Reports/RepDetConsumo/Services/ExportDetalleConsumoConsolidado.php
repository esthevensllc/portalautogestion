<?php

namespace AMovil\Reports\RepDetConsumo\Services;

use AMovil\Reports\RepDetConsumo\Infrastructure\Repository\LaravelDetalleConsumoRepository;
use AMovil\Reports\ReportLog\Domain\ReportLogStatus;
use AMovil\Reports\ReportLog\Services\SaveReportDto;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;
use DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Throwable;

class ExportDetalleConsumoConsolidado
{
    private $repo;
    private $exportService;
    private $saveReportLog;
    public function __construct(LaravelDetalleConsumoRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke(string $cliente, ?string $periodo, string $unidad_trafico_id, string $unidad_consumo_id, string $consumo_sin_cargo,$tipo_input, $fecha1, $fecha2)
    {
        $dt_start = new DateTime();
        $reportInput = ['tipoInput' => $tipo_input, 'consumoSinCargo' => $consumo_sin_cargo];
        try {
            $dt_periodos = [];
            $periodos = [];
            if($tipo_input === '1'){
                $periodos = explode(",", trim($periodo));
                $reportInput["periodos"] = $periodos;
            }else{
                $periodos = $this->repo->getPeriodosByFechas(
                    $cliente,
                    DateTime::createFromFormat("Y-m-d", $fecha1),
                    DateTime::createFromFormat("Y-m-d", $fecha2)
                );
                $reportInput["cliente"] = $cliente;
                $reportInput["fechaInicio"] = $fecha1;
                $reportInput["fechaFin"] = $fecha2;
            }
            // dd($periodos);

            $str_periodo = [];
            $last_period = $periodos[0];
            foreach($periodos as $p){
                $dt_periodo = DateTime::createFromFormat('Ym', $p);
                $dt_periodos[] = $dt_periodo;
                $str_periodo[] = $dt_periodo->format('Y/m');
                $last_period = $p;
            }
            $str_periodo = implode(",", $str_periodo);
            if($tipo_input === "2"){
                $str_periodo = DateTime::createFromFormat("Y-m-d", $fecha1)->format("d/m/Y")." al ".DateTime::createFromFormat("Y-m-d", $fecha2)->format("d/m/Y");
            }

            $title = $periodos[0];
            if($periodos[0] !== $last_period){
                $title = $periodos[0]." - ".$last_period;
            }

            $customer_full_name = '';
            foreach($periodos as $p){
                $full_name = $this->repo->getCustomerFullName($cliente, $p);
                if($full_name !== null){
                    $customer_full_name = $full_name;
                }
            }

            $this->exportService->loadData([
                "f1" => ['label' => 'CONSOLIDADO DE MINUTOS'],
                "f2" => ['label' => ''],
                "f3" => ['label' => ''],
                "f4" => ['label' => ''],
                "f5" => ['label' => ''],
                "f6" => ['label' => ''],
            ], [
                ['f1' => ''],
                ['f1' => "Periodo              : {$str_periodo}"],
                ['f1' => "Nombre Cliente : {$customer_full_name}"],
                ['f1' => "Nro. Cuenta       : {$cliente}"]
            ], [
                'rowType' => 'array',
                'validateProps' => true,
                'sheetIndex' => 0,
                'title' => $title,
                'y_start_index' => 1,
                'x_start_index' => 1,
                'styles' => [
                    'header' => ['font' => ['bold' => true, 'size' => 14]],
                    'body' => [
                        'font' => ['bold' => true, 'size' => 12],
                        'borders'=> [
                            'bottom' => ['borderStyle' => SpreadsheetStyle\Border::BORDER_THIN, 'color' => array('rgb'=>'000000')]
                        ]
                    ]
                ]
            ]);

            $numberFormat = ['numberFormat' => ['formatCode' => SpreadsheetStyle\NumberFormat::FORMAT_NUMBER_00]];

            $unidad_trafico_label = $this->getUnidadTraficoLabel($unidad_trafico_id);

            $headers = [
                // "cuenta" => ['label' => 'cuenta'],
                "nro_telefono" => ['label' => 'Nro Telefono'],
                "factura" => ['label' => 'Nro Factura'],
                "ciclo" => ['label' => 'Ciclo'],
                "cantidad_gprs" => ['label' => "Cantidad Gprs (KB)", 'bodyStyles' => $numberFormat],
                "cantidad_gprs_mb" => ['label' => "Cantidad Gprs (MB)", 'bodyStyles' => $numberFormat],
                "cantidad_gprs_gb" => ['label' => "Cantidad Gprs (GB)", 'bodyStyles' => $numberFormat],
                "duracion_roaming_datos" => ['label' => 'Duracion Roaming Datos', 'bodyStyles' => $numberFormat],
                "total_cantidad" => ['label' => "Total Cantidad({$unidad_trafico_label})", 'bodyStyles' => $numberFormat],
                "cantidad_sms" => ['label' => 'Cantidad Sms', 'bodyStyles' => $numberFormat],
                "cantidad_mms" => ['label' => 'Cantidad Mms', 'bodyStyles' => $numberFormat],
                "duracion_rpc" => ['label' => 'Duracion Rpc', 'bodyStyles' => $numberFormat],
                "duracion_onnet" => ['label' => 'Duracion Onnet', 'bodyStyles' => $numberFormat],
                "duracion_onnet_adi" => ['label' => 'Duracion Onnet Adi', 'bodyStyles' => $numberFormat],
                "duracion_offnetfijo" => ['label' => 'Duracion Offnetfijo', 'bodyStyles' => $numberFormat],
                "duracion_offnetmovil" => ['label' => 'Duracion Offnetmovil', 'bodyStyles' => $numberFormat],
                "duracion_roaming_voz" => ['label' => 'Duracion Roaming Voz', 'bodyStyles' => $numberFormat],
                "duracion_ldn" => ['label' => 'Duracion Ldn', 'bodyStyles' => $numberFormat],
                "duracion_ldi" => ['label' => 'Duracion Ldi', 'bodyStyles' => $numberFormat],
                // "duracion_sin_cargo" => ['label' => 'Duracion Sin Cargo', 'bodyStyles' => $numberFormat],
            ];

            if($consumo_sin_cargo === "1"){
                $headers["duracion_sin_cargo"] = ['label' => 'Duracion Sin Cargo', 'bodyStyles' => $numberFormat];
            }

            $options = [
                // 'rowType' => 'array',
                'sheetIndex' => 0,
                'title' => $title,
                'y_start_index' => 8,
                'x_start_index' => 1,
                'styles' => [
                    'header' => [
                        'font' => ['bold' => true, 'size' => 9],
                        'borders'=> [
                            'allBorders' => [
                                'borderStyle' => SpreadsheetStyle\Border::BORDER_THIN,
                                'color' => array('rgb'=>'000000')
                            ]
                        ],
                    ],
                    'body' => ['font' => ['size' => 9]]
                ]
            ];
            
            $periodos_ok = [];
            foreach($periodos as $p){
                if($this->repo->hasRecords($cliente, $p)){
                    $periodos_ok[] = $p;
                }
            }

            
            
            if($tipo_input === '1'){
                $this->repo->generateReporteConsolidado($cliente, $periodos_ok, $unidad_trafico_id, $unidad_consumo_id, null, null);
            }else{
                $this->repo->generateReporteConsolidado($cliente, $periodos_ok, $unidad_trafico_id, $unidad_consumo_id, DateTime::createFromFormat("Y-m-d", $fecha1), DateTime::createFromFormat("Y-m-d", $fecha2));
            }

            $data = $this->repo->getReporteConsolidado();
            
            $this->exportService->loadData($headers, $data, $options);

            $sheet = $this->exportService->getExportReference()->getActiveSheet();
            //$sheet = new Spreadsheet();
            //$sheet = $sheet->getActiveSheet();
            $finalColumn = "S";
            if($consumo_sin_cargo === "1"){
                $finalColumn = "T";
            }
            $sheet->getStyle("B8:{$finalColumn}8")->applyFromArray([
                'font' => ['bold' => true, 'size' => 9],
                'borders'=> [
                    'allBorders' => [
                        'borderStyle' => SpreadsheetStyle\Border::BORDER_THIN,
                        'color' => array('rgb'=>'000000')
                    ]
                ],
                'alignment' => [
                    'horizontal' => SpreadsheetStyle\Alignment::HORIZONTAL_CENTER,
                    'vertical' => SpreadsheetStyle\Alignment::VERTICAL_CENTER,
                    // 'wrapText' => true
                ]
            ]);
            $sheet->mergeCells('E8:H8')->setCellValue('E8', 'DATOS');
            $sheet->mergeCells('I8:I9')->setCellValue('I8', "Total Cantidad({$unidad_trafico_label})");
            $sheet->mergeCells('J8:K8')->setCellValue('J8', 'MENSAJES');
            $sheet->mergeCells("L8:{$finalColumn}8")->setCellValue('L8', 'VOZ');

            $columns_to_autosize = ['C','D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T'];

            foreach($columns_to_autosize as $col){
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = "CONSOLIDADO_".$dt_start->format('YmdHis').".xlsx";
            $filePath = SaveReportLog::LOCAL_PATH."/DETALLE_CONSUMO/{$filename}";
            
            $this->exportService->getWriter(WriterType::XLSX)->save($filePath);
            
            $this->reportLog($filePath, $filename, $dt_start, new DateTime(), $reportInput, null);
            
            $excel_content = file_get_contents($filePath);

            return $excel_content;
        } catch (\Throwable $th) {
            $this->reportLog(null, null, $dt_start, new DateTime(), $reportInput, $th);
            throw $th;
        }
    }

    public function getUnidadTraficoLabel($id){
        $unidad_trafico_by_id = [
            '1' => 'KB',
            '2' => 'MB',
            '3' => 'GB',
        ];
        return $unidad_trafico_by_id[$id];
    }

    private function reportLog(?string $local_file, ?string $filename, DateTime $ini, DateTime $fin, array $input, ?Throwable $error)
    {
        $logDto = SaveReportDto::create(
            'DETALLE_CONSUMO',
            $filename,
            $ini,
            $fin,
            $error === null ? ReportLogStatus::CORRECTO : ReportLogStatus::ERROR,
            $error !== null ? $error->getMessage() : null,
            'detallado_consumo.consolidado',
            json_encode($input)
        );
        $this->saveReportLog->create($logDto);
        if ($local_file !== null) {
            $this->saveReportLog->sendFileToRemoteServer($local_file, "DETALLE_CONSUMO/{$filename}");
        }
    }
}
