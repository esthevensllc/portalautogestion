<?php

namespace App\Exports\facturacionFija;

use AMovil\Auth\AccessControl\Domain\AuthService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class FacturacionFijaSFExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, ShouldAutoSize, WithTitle
{
    use Exportable;

    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function collection()
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        return collect(DB::connection('oracle')->select(DB::RAW("select distinct
        codcli,
        telefono_origen,
        telefono_destino,
        servicio,
        nombre_destino,
        tipo_destino,
        horaini,
        horafin,
        minutos,
        segundos,
        idtiphor,
        tarifa,
        monto,
        idcon,
        idoperador,
        Operador,
        idclaisdest,
        Clase_Destino,
        idgrpdes,
        Grupo_Destino,
        cantidadval,
        cantidadorigen
        from USRAES.TB_FIJA_SIN_FACTURADA_{$this->userIdentifier}")));
    }

    public function headings(): array
    {
        return [
            'CODCLI', 'TELEFONO_ORIGEN', 'TELEFONO_DESTINO'	,'SERVICIO',
            'NOMBRE_DESTINO',	'TIPO_DESTINO',	'HORAINI',	'HORAFIN',	'MINUTOS',	'SEGUNDOS',	'IDTIPHOR',
            'TARIFA',	'MONTO', 'IDCON'	,'IDOPERADOR'	,'OPERADOR'	,'IDCLAISDEST'
            ,'CLASE_DESTINO'	,'IDGRPDES'	,'GRUPO_DESTINO',	'CANTIDADVAL'	,'CANTIDADORIGEN'	
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_GENERAL,
            'I' => NumberFormat::FORMAT_NUMBER_00,
            'J' => NumberFormat::FORMAT_NUMBER_00,
            'L' => NumberFormat::FORMAT_NUMBER_00,
            'N' => NumberFormat::FORMAT_NUMBER_00,
            'O' => NumberFormat::FORMAT_NUMBER_00,
            'T' => NumberFormat::FORMAT_NUMBER_00,
            'U' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $count = count($this->collection());
        
        //$index = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($count+1);

        $sheet->getStyle("A2:V".$count+1)->applyFromArray([
            'font' => ['size' => 9],
            'borders'=> [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => array('rgb'=>'000000')
                ]
            ]
        ]);

        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],

            'A1:V1' => [
                'font' => ['size' => 9],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    //'rotation' => 90,
                    /*'startColor' => [
                        'argb' => '5B9AD5',
                    ],
                    'endColor' => [
                        'argb' => '00FF0000',
                    ],*/
                ],
                'borders'=> [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => array('rgb'=>'000000')
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ]
            ],
        ];
    }

    public function title(): string
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $result = DB::connection('oracle')->select(DB::RAW("select telefono_origen from USRAES.TB_FIJA_SIN_FACTURADA_{$this->userIdentifier} where rownum = 1"));
        if(isset($result[0])){
            return 'TEF FIJO '.$result[0]->telefono_origen;
        }else{
            return 'TEF FIJO';
        }
        
    }
}