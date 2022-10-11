<?php

namespace App\Exports;

use App\Models\ReportesSigrei\sigrei_export;
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

class ReporteSigreiExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    use Exportable;

    public function collection()
    {
        return collect(DB::select(DB::RAW("select contador,imei, tipo, to_char(fecha, 'dd/mm/yyyy hh24:mi:ss') as fecha, numero_servicio_telefonico, apellido_paterno, apellido_materno, nombres, tipo_doc, nro_documento, razon_social from TMP_FINAL_2")));
        /*
        return collect(DB::connection('mysql')->select(DB::RAW("SELECT IMEI, estado_del_reporte, fecha_reporte, msisdn, TRIM(SUBSTRING_INDEX(APELLIDOS,' ',1)) APELLIDO_PATERNO, TRIM(SUBSTRING(APELLIDOS,LENGTH(SUBSTRING_INDEX(APELLIDOS,' ',1))+2,LENGTH(APELLIDOS))) APELLIDO_MATERNO, NOMBRES, CASE WHEN UPPER(TIPO_DOCUMENTO)='DNI' THEN 1 WHEN UPPER(TIPO_DOCUMENTO)='RUC' THEN 2 WHEN TIPO_DOCUMENTO='Carnet Extranjería' or TIPO_DOCUMENTO='C.E' or TIPO_DOCUMENTO='C.E.' or TIPO_DOCUMENTO='CE' or TIPO_DOCUMENTO='CARNET EXTRANJERÍA' THEN 3 WHEN UPPER(TIPO_DOCUMENTO)='PASAPORTE' THEN 4 WHEN UPPER(TIPO_DOCUMENTO)='DOCUMENTO LEGAL DE IDENTIDAD VALIDO REQUERIDO POR LA SNM' THEN 5 ELSE TIPO_DOCUMENTO END TIPO_DOCUMENTO_LEGAL, NRO_DOCUMENTO, IF(UPPER(TIPO_DOCUMENTO)='RUC',NN.CCNAME,NULL) RAZON_SOCIAL FROM ( SELECT DISTINCT ZZ.IMEI, ZZ.estado_del_reporte, ZZ.fecha_reporte, ZZ.msisdn, ZZ.TER_ESTADO, IF(ZZ.MES IS NULL,VV.MES,ZZ.MES) MES, IF(ZZ.TIPO_DOCUMENTO IS NULL,VV.TIPO_DOCUMENTO,ZZ.TIPO_DOCUMENTO) TIPO_DOCUMENTO, IF(ZZ.NRO_DOCUMENTO IS NULL,VV.NRO_DOCUMENTO,ZZ.NRO_DOCUMENTO) NRO_DOCUMENTO, IF(ZZ.NOMBRES IS NULL,VV.NOMBRES,ZZ.NOMBRES) NOMBRES, IF(ZZ.APELLIDOS IS NULL,VV.APELLIDOS,ZZ.APELLIDOS) APELLIDOS FROM ( SELECT DISTINCT XX.IMEI, XX.estado_del_reporte, XX.fecha_reporte fecha_reporte, XX.msisdn, XX.TER_ESTADO, YY.MES, YY.TIPO_DOCUMENTO, YY.NRO_DOCUMENTO, YY.NOMBRES, YY.APELLIDOS FROM ( SELECT DISTINCT AA.IMEI, AA.estado_del_reporte, BB.FECHA fecha_reporte, BB.msisdn, BB.TER_ESTADO FROM ( SELECT IMEI,estado_del_reporte,STR_TO_DATE(fecha_reporte, '%d/%m/%Y') fecha_reporte FROM dwo.reporte_sigrei_tmp ) AA LEFT JOIN dwo.ter_claro_movimiento BB ON AA.IMEI=BB.IMEI AND SUBSTRING(AA.fecha_reporte,1,10)=SUBSTRING(BB.FECHA,1,10) )XX LEFT JOIN DWO.F_M_ABONADOS YY on XX.MSISDN=YY.MSISDN AND YY.MES=CONCAT(SUBSTRING(XX.fecha_reporte,1,4),SUBSTRING(XX.fecha_reporte,6,2)) )ZZ LEFT JOIN DWO.F_M_ABONADOS VV on ZZ.MSISDN=VV.MSISDN AND VV.MES=CONCAT(SUBSTRING(date_sub(ZZ.fecha_reporte, interval 1 month),1,4),SUBSTRING(date_sub(ZZ.fecha_reporte, interval 1 month),6,2)) )MM LEFT JOIN ( SELECT CUSTOMER_ID, CCNAME, CSCOMPREGNO FROM ( SELECT CUSTOMER_ID, CCNAME, CSCOMPREGNO, ROW_NUMBER() OVER(PARTITION BY CSCOMPREGNO ORDER BY CUSTOMER_ID DESC) FLAG FROM dwo.sa_ccontact_all WHERE CSCOMPREGNO IN ( SELECT NRO_DOCUMENTO FROM ( SELECT DISTINCT ZZ.IMEI, ZZ.msisdn, IF(ZZ.TIPO_DOCUMENTO IS NULL,VV.TIPO_DOCUMENTO,ZZ.TIPO_DOCUMENTO) TIPO_DOCUMENTO, IF(ZZ.NRO_DOCUMENTO IS NULL,VV.NRO_DOCUMENTO,ZZ.NRO_DOCUMENTO) NRO_DOCUMENTO FROM ( SELECT DISTINCT XX.IMEI, XX.fecha_reporte fecha_reporte, XX.msisdn, YY.TIPO_DOCUMENTO, YY.NRO_DOCUMENTO FROM ( SELECT DISTINCT AA.IMEI, BB.FECHA fecha_reporte, BB.msisdn FROM ( SELECT IMEI,estado_del_reporte,STR_TO_DATE(fecha_reporte, '%d/%m/%Y') fecha_reporte FROM dwo.reporte_sigrei_tmp ) AA LEFT JOIN dwo.ter_claro_movimiento BB ON AA.IMEI=BB.IMEI AND SUBSTRING(AA.fecha_reporte,1,10)=SUBSTRING(BB.FECHA,1,10) )XX LEFT JOIN DWO.F_M_ABONADOS YY on XX.MSISDN=YY.MSISDN AND YY.MES=CONCAT(SUBSTRING(XX.fecha_reporte,1,4),SUBSTRING(XX.fecha_reporte,6,2)) )ZZ LEFT JOIN DWO.F_M_ABONADOS VV on ZZ.MSISDN=VV.MSISDN AND VV.MES=CONCAT(SUBSTRING(date_sub(ZZ.fecha_reporte, interval 1 month),1,4),SUBSTRING(date_sub(ZZ.fecha_reporte, interval 1 month),6,2)) ) GG WHERE TIPO_DOCUMENTO='RUC' ) )S WHERE FLAG=1 )NN ON MM.NRO_DOCUMENTO=NN.CSCOMPREGNO")));
        */
    }

    public function headings(): array
    {
        return [
            'Nro.',
            'IMEI',
            'ESTADO DEL REPORTE',
            'FECHA DEL REPORTE 
            (dd/mm/aaaa  hh:mi:ss)',
            'NÚMERO DE SERVICIO',
            'APELLIDO PATERNO DEL POSIBLE TITULAR DEL EQUIPO',
            'APELLIDO MATERNO DEL POSIBLE TITULAR DEL EQUIPO',
            'NOMBRES DEL POSIBLE TITULAR DEL EQUIPO',
            'TIPO DE DOCUMENTO LEGAL
            1=DNI,
            2=RUC,
            3=Carné de Extranjería,
            4=Pasaporte,
            5=Documento Legal de Identidad válido requerido por la SNM.',
            'NÚMERO DE DOCUMENTO LEGAL',
            'RAZON SOCIAL'
        ];
    }

    /*
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
            'use_bom' => true,
            'output_encoding' => 'UTF-8',
            'enclosure' => ''
        ];
    }
    */

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_GENERAL,
            'B' => NumberFormat::FORMAT_NUMBER
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $count = count($this->collection());
        
        //$index = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($count+1);

        $sheet->getStyle("A2:K".$count+1)->applyFromArray([
            'borders'=> [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => array('rgb'=>'000000')
                ]
            ]
        ]);

        $sheet->setTitle('IMEI');

        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],

            'A1:K1' => [
                //'font' => ['color' => ['argb' => '00FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    //'rotation' => 90,
                    'startColor' => [
                        'argb' => '5B9AD5',
                    ],
                    /*'endColor' => [
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
}