<?php

namespace AMovil\Reports\General\ComprobantePago\Services;

use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportPlantillaComprobantePago
{
    private $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    public function __invoke()
    {
        $headers = [
            "campo" => ["label" => "CAMPO"],
            "tipo" => ["label" => "TIPO"],
            "descripcion" => ["label" => "DESCRIPCIÓN"],
            "muestra" => ["label" => "MUESTRA"],
            "requerido" => ["label" => "REQUERIDO"],
        ];
        $data = [
            [
                "campo" => "N°",
                "tipo" => "Entrada/Salida",
                "descripcion" => "Indice del Registro",
                "muestra" => "1",
                "requerido" => "NO"
            ],
            [
                "campo" => "PLATAFORMA",
                "tipo" => "Entrada/Salida",
                "descripcion" => "Sistema Fuente o Plataforma de Origen de la emisión del Comprobante",
                "muestra" => "BSCS",
                "requerido" => "SI"
            ],
            [
                "campo" => "NRO_COMPROBANTE",
                "tipo" => "Entrada/Salida",
                "descripcion" => "Serie y Número (o Correlativo) del Comprobante",
                "muestra" => "SB01-0181371484",
                "requerido" => "SI"
            ],
            [
                "campo" => "TIPO_COMPROBANTE",
                "tipo" => "Entrada/Salida",
                "descripcion" => "Tipo de Comprobante según la plataforma. No es el Tipo de Comprobante según conceptos contables de la SUNAT.",
                "muestra" => "IN",
                "requerido" => "NO"
            ],
            [
                "campo" => "FECHA_EMISION",
                "tipo" => "Entrada/Salida",
                "descripcion" => "Fecha de Emisión del Comprobante",
                "muestra" => "24/01/2022",
                "requerido" => "SI"
            ],
            [
                "campo" => "RAZON_SOCIAL",
                "tipo" => "Entrada/Salida",
                "descripcion" => "Razon Social o Nombre del Titular del comprobante emitido",
                "muestra" => "JAVIER RAMON SALAZAR FLORES",
                "requerido" => "NO"
            ],
            [
                "campo" => "CUSTOMER_ID",
                "tipo" => "Salida",
                "descripcion" => "Cuenta del cliente asociado al comprobante emitido. Sólo aplica para comprobantes emitidos de la Plataforma BSCS (BSCS07 o BSCSIX)",
                "muestra" => "43071352",
                "requerido" => "NO"
            ],
            [
                "campo" => "IDCON",
                "tipo" => "Salida",
                "descripcion" => "Valor del IDCON del comprobante emitido. Sólo aplica para comprobantes emitidos de la Plataforma SGA.",
                "muestra" => "N.A.",
                "requerido" => "NO"
            ],
            [
                "campo" => "IDCON_ASOCIADO_NC_ND",
                "tipo" => "Salida",
                "descripcion" => "Valor del IDCON del comprobante afecto por la N/C o N/D emitido. Sólo aplica para comprobantes emitidos de la Plataforma SGA.",
                "muestra" => "N.A.",
                "requerido" => "NO"
            ],
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
            'rowType' => "array",
            'title' => "DETALLE",
            'styles' => [
                'header' => [
                    'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFF']],
                    'borders'=> [
                        'allBorders' => ['borderStyle' => SpreadsheetStyle\Border::BORDER_THIN, 'color' => array('rgb'=>'000000')]
                    ],
                    'fill' => [
                        'fillType' => SpreadsheetStyle\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'C00000'],
                    ],
                ],
                'body' => [
                    'font' => ['size' => 9],
                    'borders'=> [
                        'allBorders' => ['borderStyle' => SpreadsheetStyle\Border::BORDER_THIN, 'color' => array('rgb'=>'000000')]
                    ],
                ]
            ]
        ]);

        $sheet = $this->exportService->getExportReference()->getActiveSheet();
        $columns_to_autosize = ['A', 'B', 'C', 'D', 'E'];

        foreach($columns_to_autosize as $col){
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $headers = [
            "n" => ["label" => "N°"],
            "plataforma" => ["label" => "Plataforma"],
            "comprobante" => ["label" => "Nº de Comprobante de Pago"],
            "tipo_comprobante" => ["label" => "Tipo de Comprobante de Pago"],
            "fecha_emision" => ["label" => "Fecha de Emisión del Comprobante de Pago"],
            "razon_social" => ["label" => "Razon Social"],
            "customer_id" => ["label" => "CUSTOMER ID"],
        ];

        $data = [
            [
                "n" => 1,
                "plataforma" => "BSCS",
                "comprobante" => "SB01-XXXXXXXXXX",
                "tipo_comprobante" => 14,
                "fecha_emision" => "01/01/2022",
                "razon_social" => "AAAAAAAAAAAAA",
                "customer_id" => "",
            ],
            [
                "n" => 2,
                "plataforma" => "BSCS",
                "comprobante" => "SB01-YYYYYYYYYY",
                "tipo_comprobante" => "",
                "fecha_emision" => "18/01/2022",
                "razon_social" => "AABBBBBBBBBBB",
                "customer_id" => "",
            ],
            [
                "n" => "",
                "plataforma" => "BSCS",
                "comprobante" => "SB01-ZZZZZZZZZZZ",
                "tipo_comprobante" => "",
                "fecha_emision" => "19/01/2022",
                "razon_social" => "CCCCCCCCCCCCCCCCC",
                "customer_id" => "",
            ],
            [
                "n" => "",
                "plataforma" => "BSCS",
                "comprobante" => "SB01-WWWWW",
                "tipo_comprobante" => "14",
                "fecha_emision" => "20/01/2022",
                "razon_social" => "",
                "customer_id" => "",
            ]
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 1,
            'rowType' => "array",
            'title' => "EJEMPLO",
            'styles' => [
                'G1:G1' => [
                    'borders'=> [
                        'allBorders' => ['borderStyle' => SpreadsheetStyle\Border::BORDER_THIN, 'color' => array('rgb'=>'000000')]
                    ],
                    'fill' => [
                        'fillType' => SpreadsheetStyle\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFF00'],
                    ],
                ],
                'A1:F1' => [
                    'font' => ['bold' => true, 'size' => 9],
                    'borders'=> [
                        'allBorders' => ['borderStyle' => SpreadsheetStyle\Border::BORDER_THIN, 'color' => array('rgb'=>'000000')]
                    ],
                    'fill' => [
                        'fillType' => SpreadsheetStyle\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'DBDBDB'],
                    ],
                ],
                'body' => [
                    'font' => ['size' => 9],
                    'borders'=> [
                        'allBorders' => ['borderStyle' => SpreadsheetStyle\Border::BORDER_THIN, 'color' => array('rgb'=>'000000')]
                    ]
                ]
            ]
        ]);

        $sheetReference = $this->exportService->getExportReference();
        $sheetReference->setActiveSheetIndex(1);
        $sheet = $sheetReference->getActiveSheet();
        $columns_to_autosize = ['A', 'B', 'C', 'D', 'E', 'F'];

        foreach($columns_to_autosize as $col){
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $content = $this->exportService->getWriter(WriterType::XLSX)->getOutput();

        return new Response([], [
            "filename" => "Comprobante_Pago.xlsx",
            "type" => "xlsx",
            "content" => $content
        ]); 
    }
}
