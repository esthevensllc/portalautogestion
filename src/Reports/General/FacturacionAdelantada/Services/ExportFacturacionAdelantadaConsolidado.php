<?php

namespace AMovil\Reports\General\FacturacionAdelantada\Services;

use AMovil\Reports\General\FacturacionAdelantada\Domain\FacturacionAdelantadaRepository;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;
use Ramsey\Uuid\Uuid;

class ExportFacturacionAdelantadaConsolidado
{
    private $repo;
    private $exportService;
    private $saveReportLog;
    private $storage_path;

    public function __construct(FacturacionAdelantadaRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
        $this->storage_path = storage_path('app/public');
    }

    public function __invoke($tipoInput, $periodo, $fechaIni, $fechaFin, $cuenta, $numeroFactura, $unidad_trafico_id, $unidad_consumo_id, $consumo_sin_cargo): Response
    {
        $dt_start = new DateTime();

        $dtFechaIni = null;
        $dtFechaFin = null;
        $data = [];
        $title = "";
        $title_periodo = "";

        if ($tipoInput === "1") {
            $dtPeriodo = DateTime::createFromFormat("Ym", $periodo);
            if($dtPeriodo === false){
                throw new Exception("El periodo '{$periodo}' es invalido");
            }
            $dtFechaIni = (clone $dtPeriodo)->modify("-1 month");
            $dtFechaFin = (clone $dtPeriodo)->modify("-1 day");
            $title = $periodo;
            $title_periodo = $periodo;
        }else{
            $dtFechaIni = DateTime::createFromFormat("Y-m-d", $fechaIni);
            $dtFechaFin = DateTime::createFromFormat("Y-m-d", $fechaFin);
            $title = $dtFechaIni->format("Ym");
            if($title !== $dtFechaFin->format("Ym")){
                $title .= " - ".$dtFechaFin->format("Ym");
            }
            $title_periodo = $dtFechaIni->format("d/m/Y")." al ".$dtFechaFin->format("d/m/Y");
        }

        $data = $this->repo->getReporteConsolidadoByDates(
            $cuenta,
            $numeroFactura,
            $unidad_trafico_id,
            $unidad_consumo_id,
            $dtFechaIni,
            $dtFechaFin
        );

        $customer_full_name = '';

        try {
            $tempFilename = $this->exportExcel($title_periodo, $title, $customer_full_name, $cuenta, $unidad_trafico_id, $consumo_sin_cargo, $data);
            $this->reportLog($tempFilename, $dt_start, new DateTime());
            
            $content = file_get_contents($tempFilename);

            return new Response([], [
                "filename" => "FACTURACION_ADELANTADA_CONSOLIDADO.xlsx",
                "type" => "xlsx",
                "content" => $content,
            ]);
        } catch (\Throwable $th) {
            $this->reportLog(null, $dt_start, new DateTime(), ['mensaje' => $th->getMessage()]);
            throw $th;
        }        
    }

    private function exportExcel($periodo, $title, $customerFullName, $cuenta, $unidad_trafico_id, $consumo_sin_cargo, $data)
    {
        $this->exportService->loadData([
            "f1" => ['label' => 'CONSOLIDADO DE MINUTOS'],
            "f2" => ['label' => ''],
            "f3" => ['label' => ''],
            "f4" => ['label' => ''],
            "f5" => ['label' => ''],
            "f6" => ['label' => ''],
        ], [
            ['f1' => ''],
            ['f1' => "Periodo              : {$periodo}"],
            ['f1' => "Nombre Cliente : {$customerFullName}"],
            ['f1' => "Nro. Cuenta       : {$cuenta}"]
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

        $this->exportService->loadData($headers, $data, $options);
        $sheet = $this->exportService->getExportReference()->getActiveSheet();

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
        
        $tempFilename = "{$this->storage_path}/".Uuid::uuid4()->toString()."xlsx";
        
        $this->exportService->getWriter(WriterType::XLSX)->save($tempFilename);

        return $tempFilename;
    }

    public function getUnidadTraficoLabel($id){
        $unidad_trafico_by_id = [
            '1' => 'KB',
            '2' => 'MB',
            '3' => 'GB',
        ];
        return $unidad_trafico_by_id[$id];
    }

    private function reportLog(?string $locaFile, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        $filename = "FA_CONSOLIDADO_".$ini->format('YmdHis').".xlsx";
        $data = array_merge([
            'name' => 'DETALLE_CONSUMO',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => 'detalle_consumo.fa_consolidado',
            "filename" => $filename
        ], $extra_data);
        $this->saveReportLog->__invoke($data, $locaFile, 'DETALLE_CONSUMO');
    }
}
