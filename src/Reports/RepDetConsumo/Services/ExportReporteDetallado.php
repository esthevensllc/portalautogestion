<?php

namespace AMovil\Reports\RepDetConsumo\Services;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\Exportable;
use DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ExportReporteDetallado implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    use Exportable;

    private $data = [];
    private $title = '';

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function setTitle($title){
        $this->title = $title;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            "CUENTA","CICLO","NRO_FACTURA","NRO_TEL_ORIGEN","FECHA","HORA_INICIO","HORA_FIN","PAIS","NRO_TEL_DESTINO","CONSUMO","TIPO_SERVICIO","DESTINO","OPERADOR","TIPO_LLAMADA","CARGO_FINAL"
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $count = count($this->data);

        $sheet->getStyle("A2:O".$count+1)->applyFromArray([
            'font' => ['size' => 9],
        ]);

        $sheet->setTitle($this->title);

        return [
            1 => ['font' => ['bold' => true, 'size' => 9]],
            'A1:O1' => [
                'font' => ['size' => 9],
                'borders'=> [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => array('rgb'=>'000000')
                    ]
                ]
            ],
        ];
    }
}
