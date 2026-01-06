<?php

namespace AMovil\Reports\ExtraccionDevolucion\MODEV\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;
use AMovil\Reports\ExtraccionDevolucion\MODEV\Domain\MODEVRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use Exception;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;
use DateTime;

class ExportMODEV
{
    private $repository;
    private $informeRepository;
    private $exportService;

    public function __construct(MODEVRepository $repository, ExtraccionRepository $informeRepository, ExportService $exportService)
    {
        $this->repository = $repository;
        $this->informeRepository = $informeRepository;
        $this->exportService = $exportService;
    }

    public function __invoke($ticket, $departamento)
    {
        $informe = $this->informeRepository->getInputByTicket($ticket);
        if ($informe === null) {
            throw new Exception("El ticket no existe");
        }
        $data = $this->repository->getReporteByTicketAndDepartamento($informe->tipo_reporte, $ticket, $departamento);
        
        $content = $this->getReportWriter($data)->getOutput();

        return new Response([], [
            "filename" => "FORMATO_MODEV_{$ticket}_{$departamento}.xlsx",
            "type" => "xlsx",
            "content" => $content,
        ]);
    }

    public function getByTickets($tickets){
        $data = $this->repository->getReporteByTickets($tickets);
        $content = $this->getReportWriter($data)->getOutput();
        $strNow = (new DateTime())->format("Ymd");

        return new Response([], [
            "filename" => "FORMATO_MODEV_{$strNow}.xlsx",
            "type" => "xlsx",
            "content" => $content,
        ]);
    }

    private function getReportWriter($data)
    {
        // $informe = $this->informeRepository->getInputByTicket($ticket);
        // if ($informe === null) {
        //     throw new Exception("El ticket no existe");
        // }
        // $data = $this->repository->getReporteByTicketAndDepartamento($informe->tipo_reporte, $ticket, $departamento);

        $default_alignment = [
            'horizontal' => SpreadsheetStyle\Alignment::HORIZONTAL_CENTER,
            'vertical' => SpreadsheetStyle\Alignment::VERTICAL_CENTER,
            'wrapText' => true
        ];
        $def_borders = [
            'allBorders' => [
                'borderStyle' => SpreadsheetStyle\Border::BORDER_THIN,
                'color' => array('rgb'=>'000000')
            ],
        ];
        $options = [
            // 'rowType' => 'array',
            'sheetIndex' => 0,
            'title' => "MODEV",
            'y_start_index' => 1,
            'x_start_index' => 0,
            'styles' => [
                'A1:E2' => [
                    'fill' => [
                        'fillType' => SpreadsheetStyle\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFF00'],
                    ],
                    'alignment' => $default_alignment,
                    'borders' => $def_borders
                ],
                'F1:J2' => [
                    'fill' => [
                        'fillType' => SpreadsheetStyle\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'DCE6F1'],
                    ],
                    'alignment' => $default_alignment,
                    'borders' => $def_borders
                ],
                'K1:K2' => [
                    'fill' => [
                        'fillType' => SpreadsheetStyle\Fill::FILL_SOLID,
                        'startColor' => ['argb' => '00B0F0'],
                    ],
                    'alignment' => $default_alignment,
                    'borders' => $def_borders
                ],
                'L1:L2' => [
                    'fill' => [
                        'fillType' => SpreadsheetStyle\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'D8E4BC'],
                    ],
                    'alignment' => $default_alignment,
                    'borders' => $def_borders
                ],
                'M1:M2' => [
                    'fill' => [
                        'fillType' => SpreadsheetStyle\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'DCE6F1'],
                    ],
                    'alignment' => $default_alignment,
                    'borders' => $def_borders
                ],
                'N1:S2' => [
                    'fill' => [
                        'fillType' => SpreadsheetStyle\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'DA9694'],
                    ],
                    'alignment' => $default_alignment,
                    'borders' => $def_borders
                ],
                'T1:T2' => [
                    'fill' => [
                        'fillType' => SpreadsheetStyle\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FABF8F'],
                    ],
                    'alignment' => $default_alignment,
                    'borders' => $def_borders
                ],
                'U1:U2' => [
                    'fill' => [
                        'fillType' => SpreadsheetStyle\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'DCE6F1'],
                    ],
                    'alignment' => $default_alignment,
                    'borders' => $def_borders
                ],
                'V1:V2' => [
                    'fill' => [
                        'fillType' => SpreadsheetStyle\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFF00'],
                    ],
                    'alignment' => $default_alignment,
                    'borders' => $def_borders
                ],
                'header' => [
                    'font' => ['bold' => false],
                    'borders'=> [
                        'allBorders' => [
                            'borderStyle' => SpreadsheetStyle\Border::BORDER_THIN,
                            'color' => array('rgb'=>'000000')
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => SpreadsheetStyle\Alignment::HORIZONTAL_CENTER,
                        'vertical' => SpreadsheetStyle\Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ]
                ],
                'body' => [
                    'borders'=> [
                        'allBorders' => [
                            'borderStyle' => SpreadsheetStyle\Border::BORDER_THIN,
                            'color' => array('rgb'=>'000000')
                        ]
                    ],
                ]
            ]
        ];
        $headers = [
            "ticket" => ["label" => "Nro de Ticket"],
            "nro_documento" => ["label" => "N° del documento (DNI,RUC, CE)"],
            "id_cliente" => ["label" => "Código del Cliente"],
            "msisdn" => ["label" => "N° Servicio"],
            "servicio_afectado" => ["label" => "Servicio Analizado"],

            "modo_contratacion" => ["label" => "Modalidad (prepago o postpago)"],
            "monto_plan" => ["label" => "Renta mensual (inc. IGV)"],
            "tiempo_averia" => ["label" => "Duración (minutos)"],
            "monto_devolver_igv" => ["label" => "Monto total devuelto más intereses (inc. IGV) (Activos) / Monto Capital (Inactivos)"],
            "unidad" => ["label" => "Moneda (Soles o Dólares)"],

            "fecha_dev_fecha_comun" => ["label" => "Fecha de devolución (activo)/ Fecha de Comunicación (inactivo)"],
            "factura_aplicada" => ["label" => "N° del recibo donde se hizo la devolución- solo cuando es activo"],
            "estado" => ["label" => "Estado (Activo, Inactivo o no Corresponde"],

            "fecha_baja" => ["label" => "Fecha de Baja del servicio"],
            "nombre_razon_social" => ["label" => "Nombre o Razón Social"],
            "lugar_donde_cobrar" => ["label" => "Lugar donde cobrar"],
            "requisito_cobro" => ["label" => "Requisitos para el cobro"],
            "comunicacion" => ["label" => "Comunicación (SI/NO)"],
            "medio_comunicacion" => ["label" => "Medio de Comunicación (correo/sms/diario)"],

            "no_corresponde" => ["label" => "Motivo- solo cuando no corresponde"],
            "comentarios" => ["label" => "Comentarios"],
            "liberado" => ["label" => "Liberado"],
        ];
        $this->exportService->loadData($headers, $data, $options);

        $sheet = $this->exportService->getExportReference()->getActiveSheet();
        $sheet->mergeCells('A1:E1')->setCellValue('A1', 'llenado por el MODEV (asegurarse que el N° de documento tenga mínimo 8 caracteres) (no modificar el resto)');
        $sheet->mergeCells('F1:J1')->setCellValue('F1', 'Todos casos');
        $sheet->setCellValue('K1', 'Activo e Inactivo');
        $sheet->setCellValue('L1', 'Sólo activo');
        $sheet->setCellValue('M1', 'todos los casos');
        $sheet->mergeCells('N1:S1')->setCellValue('N1', 'sólo si es Inactivo');
        $sheet->setCellValue('T1', 'Sólo si es no corresponde');
        $sheet->setCellValue('U1', 'para todo los casos, que se requiera, puede quedar en blanco)');
        $sheet->setCellValue('V1', 'llenado por el MODEV (no modificar)');

        $columns_to_set = ['B','C','D', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V'];
        $columns_to_autosize = ['A','E','U'];

        foreach($columns_to_set as $col){
            $sheet->getColumnDimension($col)->setWidth(20);
        }
        foreach($columns_to_autosize as $col){
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->exportService->getWriter(WriterType::XLSX);
        // $content = $this->exportService->getWriter(WriterType::XLSX)->getOutput();
        // return new Response([], [
        //     "filename" => "FORMATO_MODEV_{$ticket}_{$departamento}.xlsx",
        //     "type" => "xlsx",
        //     "content" => $content,
        // ]);
    }
}
