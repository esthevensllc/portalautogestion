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

class FacturacionFijaExport implements FromCollection, WithHeadings, WithCustomCsvSettings
{
    use Exportable;

    private $authService;
    private $userIdentifier;
    protected $data;

    public function __construct($data, AuthService $authService)
    {
        $this->authService = $authService;
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'CODCLI',
            'SERIE_RECIBO',
            'NUM_RECIBO',
            'TELEFONO_ORIGEN',
            'TELEFONO_DESTINO',
            'SERVICIO',
            'NOMBRE_DESTINO',
            'TIPO_DESTINO',	
            'HORAINI',	
            'HORAFIN',	
            'MINUTOS',	
            'SEGUNDOS',	
            'IDTIPHOR',
            'TARIFA',	
            'MONTO',	
            'NOMCLI', 
            'IDCON',
            'IDOPERADOR',
            'OPERADOR',
            'IDCLAISDEST',
            'CLASE_DESTINO',
            'IDGRPDES',
            'GRUPO_DESTINO',
            'CANTIDADVAL',
            'CANTIDADORIGEN'	
        ];
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
            'enclosure' => '',
            'line_ending' => PHP_EOL,
            'use_bom' => true,
        ];
    }    
}