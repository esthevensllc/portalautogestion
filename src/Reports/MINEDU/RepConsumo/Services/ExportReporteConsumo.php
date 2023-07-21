<?php

namespace AMovil\Reports\MINEDU\RepConsumo\Services;

use AMovil\Reports\MINEDU\RepConsumo\Domain\ReporteConsumoRepository;
use AMovil\Reports\MINEDU\RepConsumo\Domain\TipoReporte;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportReporteConsumo
{
    private $repo;
    private $exportService;

    public function __construct(ReporteConsumoRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($tipo_input, $excel, $num_cuenta, $fecha1, $fecha2, $tipo_reporte): Response
    {
        $tipo = TipoReporte::getById($tipo_reporte);
        $dt_fecha1 = DateTime::createFromFormat("Y-m-d", $fecha1);
        $dt_fecha2 = DateTime::createFromFormat("Y-m-d", $fecha2);

        $headers = [];

        switch ($tipo_input) {
            case '1':
                $excel_data1 = $this->getDataFromExcel($excel);
                $this->repo->loadPlanDatos($excel_data1["data"]);

                $data = $this->repo->getByConsumoPLanDatos_Tipo($dt_fecha1, $dt_fecha2, $tipo->getId());
                /*$headers = [
                    "contratista" => ["label" => "Contratista"],
                    "serialdelchip" => ["label" => "Número Serial del Chip", 'bodyStyles' => $textoFormat],
                    "linea" => ["label" => "ID/Número de Identificación"],
                    "plan" => ["label" => "Plan (capacidad en GB)"],
                    "tecnologia" => ["label" => "Tecnologia de red (3G y/o 4G)"],
                    "servicio" => ["label" => "Fecha del inicio del periodo de facturación del servicio "],
                    "periodo" => ["label" => "periodo de facturación del servicio"],
                    "consumototal" => ["label" => "Consumo total del periodo facturado (en MB)", 'bodyStyles' => $numberFormat],
                    "costoplan" => ["label" => "Costo del Plan contratado"],
                ];*/
                $options = [
                    'sheetIndex' => 0,
                    'title' => "Estado de activación de chips",
                    'rowType' => 'array',
                    'styles' => [
                        'header' => [
                            'fill' => [
                                'fillType' => SpreadsheetStyle\Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'BDD7EE',],
                            ],
                            'font' => ['bold' => true, 'size' => 8],
                            'borders'=> [
                                'allBorders' => [
                                    'borderStyle' => SpreadsheetStyle\Border::BORDER_THIN,
                                    'color' => array('rgb'=>'000000')
                                ]
                            ],
                            'alignment' => [
                                'horizontal' => SpreadsheetStyle\Alignment::HORIZONTAL_CENTER,
                                'vertical' => SpreadsheetStyle\Alignment::VERTICAL_CENTER,
                                'wrapText' => true
                            ]
                        ],
                        'body' => [
                            'font' => ['size' => 8],
                            'borders'=> [
                                'allBorders' => [
                                    'borderStyle' => SpreadsheetStyle\Border::BORDER_THIN,
                                    'color' => array('rgb'=>'000000')
                                ]
                            ],
                        ]
                    ]
                ];

                $excel_data = $this->getEstadoActivacionChips($excel);

                $this->exportService->loadData($excel_data["headers"], $excel_data["data"], $options);
                $sheet = $this->exportService->getExportReference()->getActiveSheet();

                $columns_to_autosize = ['A'];
                foreach($columns_to_autosize as $col){
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $options["sheetIndex"] = 1;
                $options["title"] = "Consumo del plan de datos";
                $options["rowType"] = "object";
                $this->exportService->loadData($excel_data1["headers"], $data, $options);
                $this->exportService->getExportReference()->setActiveSheetIndex(1);
                $sheet = $this->exportService->getExportReference()->getActiveSheet();
                $sheet->getStyle("H1")->applyFromArray([
                    'fill' => [
                        'fillType' => SpreadsheetStyle\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFF00'],
                    ],
                ]);

                $columns_to_autosize = ['A','G'];
                foreach($columns_to_autosize as $col){
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                $excel_data = $this->getLeyendasFromExcel($excel);
                $this->exportService->loadData($excel_data["headers"], $excel_data["data"], [
                    'sheetIndex' => 2,
                    'title' => "Leyenda",
                    'rowType' => 'array',
                ]);
                $this->exportService->getExportReference()->setActiveSheetIndex(2);
                $sheet = $this->exportService->getExportReference()->getActiveSheet();
                $columns_to_autosize = ['A','B'];
                foreach($columns_to_autosize as $col){
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                $this->exportService->getExportReference()->setActiveSheetIndex(0);
                break;
            default:
                # code...
                break;
        }

        $content = $this->exportService->getWriter(WriterType::XLSX)->getOutput();

        return new Response([], [
            "filename" => $tipo->getLabel().".xlsx",
            "content" => $content,
        ]);
    }

    public function getEstadoActivacionChips($filename)
    {
        $values = [];
        $reader = IOFactory::createReader('Xlsx');
        // $reader = IOFactory::createReader('Csv');
        $spreedsheet = $reader->load($filename);
        //$spreedsheet = $reader->load("/space/reportes/DAPU/TRAMITE_CONSULTA/input/REQ_OSIPTEL_DAPU-04060_02420_Con_Estado.csv");
        $sheet = $spreedsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();

        $headers = [];

        $textoFormat = ['numberFormat' => ['formatCode' => SpreadsheetStyle\NumberFormat::FORMAT_TEXT]];

        for ($i=1; $i <= 1; $i++) {
            $row = [
                'contratista' => ["label" => $sheet->getCellByColumnAndRow(1, $i)->getValue()],
                'SerialdelChip' => ["label" => $sheet->getCellByColumnAndRow(2, $i)->getValue(), 'bodyStyles' => $textoFormat],
                'linea' => ["label" => $sheet->getCellByColumnAndRow(3, $i)->getValue()],
                'plan' => ["label" => $sheet->getCellByColumnAndRow(4, $i)->getValue()],
                'tecnologia' => ["label" => $sheet->getCellByColumnAndRow(5, $i)->getValue()],
                'estado' => ["label" => $sheet->getCellByColumnAndRow(6, $i)->getValue()],
            ];
            $headers = $row;
        }

        for ($i=2; $i <= $highestRow; $i++) {
            $row = [
                'contratista' => $sheet->getCellByColumnAndRow(1, $i)->getValue(),
                'SerialdelChip' => $sheet->getCellByColumnAndRow(2, $i)->getValue(),
                'linea' => $sheet->getCellByColumnAndRow(3, $i)->getValue(),
                'plan' => $sheet->getCellByColumnAndRow(4, $i)->getValue(),
                'tecnologia' => $sheet->getCellByColumnAndRow(5, $i)->getValue(),
                'estado' => $sheet->getCellByColumnAndRow(6, $i)->getValue(),
            ];
            $values[] = $row;
        }
        return ["headers" => $headers, "data" => $values];
    }

    public function getDataFromExcel($filename)
    {
        $values = [];
        $reader = IOFactory::createReader('Xlsx');
        // $reader = IOFactory::createReader('Csv');
        $spreedsheet = $reader->load($filename);
        //$spreedsheet = $reader->load("/space/reportes/DAPU/TRAMITE_CONSULTA/input/REQ_OSIPTEL_DAPU-04060_02420_Con_Estado.csv");
        $sheet = $spreedsheet->getSheet(1);
        $highestRow = $sheet->getHighestRow();

        $headers = [];

        $textoFormat = ['numberFormat' => ['formatCode' => SpreadsheetStyle\NumberFormat::FORMAT_TEXT]];
        $numberFormat = [
            'numberFormat' => ['formatCode' => SpreadsheetStyle\NumberFormat::FORMAT_NUMBER_00],
            'fill' => [
                'fillType' => SpreadsheetStyle\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFF00'],
            ],
        ];

        for ($i=1; $i <= 1; $i++) {
            $row = [
                'contratista' => ["label" => $sheet->getCellByColumnAndRow(1, $i)->getValue()],
                'serialdelchip' => ["label" => $sheet->getCellByColumnAndRow(2, $i)->getValue()],
                'linea' => ["label" => $sheet->getCellByColumnAndRow(3, $i)->getValue(), 'bodyStyles' => $textoFormat],
                'plan' => ["label" => $sheet->getCellByColumnAndRow(4, $i)->getValue()],
                'tecnologia' => ["label" => $sheet->getCellByColumnAndRow(5, $i)->getValue()],
                'servicio' => ["label" => $sheet->getCellByColumnAndRow(6, $i)->getValue()],
                'periodo' => ["label" => $sheet->getCellByColumnAndRow(7, $i)->getValue()],
                'consumototal' => ["label" => $sheet->getCellByColumnAndRow(8, $i)->getValue(), 'bodyStyles' => $numberFormat],
                'costoplan' => ["label" => $sheet->getCellByColumnAndRow(9, $i)->getValue()],
            ];
            $headers = $row;
        }

        for ($i=2; $i <= $highestRow; $i++) {
            $row = [
                'contratista' => $sheet->getCellByColumnAndRow(1, $i)->getValue(),
                'serialdelchip' => $sheet->getCellByColumnAndRow(2, $i)->getValue(),
                'linea' => $sheet->getCellByColumnAndRow(3, $i)->getValue(),
                'plan' => $sheet->getCellByColumnAndRow(4, $i)->getValue(),
                'tecnologia' => $sheet->getCellByColumnAndRow(5, $i)->getValue(),
                'servicio' => $sheet->getCellByColumnAndRow(6, $i)->getValue(),
                'periodo' => $sheet->getCellByColumnAndRow(7, $i)->getValue(),
                'consumototal' => $sheet->getCellByColumnAndRow(8, $i)->getValue(),
                'costoplan' => $sheet->getCellByColumnAndRow(9, $i)->getValue(),
            ];
            $values[] = $row;
        }
        return ["headers" => $headers, "data" => $values];
    }

    public function getLeyendasFromExcel($filename)
    {
        $values = [];
        $reader = IOFactory::createReader('Xlsx');
        // $reader = IOFactory::createReader('Csv');
        $spreedsheet = $reader->load($filename);
        //$spreedsheet = $reader->load("/space/reportes/DAPU/TRAMITE_CONSULTA/input/REQ_OSIPTEL_DAPU-04060_02420_Con_Estado.csv");
        $sheet = $spreedsheet->getSheet(2);
        $highestRow = $sheet->getHighestRow();

        $headers = [
            'leyenda1' => ["label" => "", "bodyStyles" => ['font' => ['bold' => true]]],
            'leyenda2' => ["label" => ""],
        ];

        for ($i=2; $i <= $highestRow; $i++) {
            $row = [
                'leyenda1' => $sheet->getCellByColumnAndRow(1, $i)->getValue(),
                'leyenda2' => $sheet->getCellByColumnAndRow(2, $i)->getValue(),
            ];
            $values[] = $row;
        }
        return ["headers" => $headers, "data" => $values];
    }
}
