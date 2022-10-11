<?php

namespace App\Exports\facturacionFija;

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

class FacturacionFijaExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, ShouldAutoSize, WithTitle
{
    use Exportable;

    public function collection()
    {
        return collect(DB::connection('oracle')->select(DB::RAW("select distinct codcli, serie_recibo, num_recibo, telefono_origen, 
        telefono_destino,servicio, nombre_destino,
        tipo_destino,
        horaini, 
        horafin,
        minutos,
        segundos, idtiphor, 
        tarifa, monto, nomcli,idcon, idoperador, Operador,
        idclaisdest, Clase_Destino, idgrpdes, Grupo_Destino,cantidadval, 
        cantidadorigen from USRAES.TB_FIJA_FACTURADA")));
        /*
        return collect(DB::connection('mysql')->select(DB::RAW("SELECT IMEI, estado_del_reporte, fecha_reporte, msisdn, TRIM(SUBSTRING_INDEX(APELLIDOS,' ',1)) APELLIDO_PATERNO, TRIM(SUBSTRING(APELLIDOS,LENGTH(SUBSTRING_INDEX(APELLIDOS,' ',1))+2,LENGTH(APELLIDOS))) APELLIDO_MATERNO, NOMBRES, CASE WHEN UPPER(TIPO_DOCUMENTO)='DNI' THEN 1 WHEN UPPER(TIPO_DOCUMENTO)='RUC' THEN 2 WHEN TIPO_DOCUMENTO='Carnet Extranjería' or TIPO_DOCUMENTO='C.E' or TIPO_DOCUMENTO='C.E.' or TIPO_DOCUMENTO='CE' or TIPO_DOCUMENTO='CARNET EXTRANJERÍA' THEN 3 WHEN UPPER(TIPO_DOCUMENTO)='PASAPORTE' THEN 4 WHEN UPPER(TIPO_DOCUMENTO)='DOCUMENTO LEGAL DE IDENTIDAD VALIDO REQUERIDO POR LA SNM' THEN 5 ELSE TIPO_DOCUMENTO END TIPO_DOCUMENTO_LEGAL, NRO_DOCUMENTO, IF(UPPER(TIPO_DOCUMENTO)='RUC',NN.CCNAME,NULL) RAZON_SOCIAL FROM ( SELECT DISTINCT ZZ.IMEI, ZZ.estado_del_reporte, ZZ.fecha_reporte, ZZ.msisdn, ZZ.TER_ESTADO, IF(ZZ.MES IS NULL,VV.MES,ZZ.MES) MES, IF(ZZ.TIPO_DOCUMENTO IS NULL,VV.TIPO_DOCUMENTO,ZZ.TIPO_DOCUMENTO) TIPO_DOCUMENTO, IF(ZZ.NRO_DOCUMENTO IS NULL,VV.NRO_DOCUMENTO,ZZ.NRO_DOCUMENTO) NRO_DOCUMENTO, IF(ZZ.NOMBRES IS NULL,VV.NOMBRES,ZZ.NOMBRES) NOMBRES, IF(ZZ.APELLIDOS IS NULL,VV.APELLIDOS,ZZ.APELLIDOS) APELLIDOS FROM ( SELECT DISTINCT XX.IMEI, XX.estado_del_reporte, XX.fecha_reporte fecha_reporte, XX.msisdn, XX.TER_ESTADO, YY.MES, YY.TIPO_DOCUMENTO, YY.NRO_DOCUMENTO, YY.NOMBRES, YY.APELLIDOS FROM ( SELECT DISTINCT AA.IMEI, AA.estado_del_reporte, BB.FECHA fecha_reporte, BB.msisdn, BB.TER_ESTADO FROM ( SELECT IMEI,estado_del_reporte,STR_TO_DATE(fecha_reporte, '%d/%m/%Y') fecha_reporte FROM dwo.reporte_sigrei_tmp ) AA LEFT JOIN dwo.ter_claro_movimiento BB ON AA.IMEI=BB.IMEI AND SUBSTRING(AA.fecha_reporte,1,10)=SUBSTRING(BB.FECHA,1,10) )XX LEFT JOIN DWO.F_M_ABONADOS YY on XX.MSISDN=YY.MSISDN AND YY.MES=CONCAT(SUBSTRING(XX.fecha_reporte,1,4),SUBSTRING(XX.fecha_reporte,6,2)) )ZZ LEFT JOIN DWO.F_M_ABONADOS VV on ZZ.MSISDN=VV.MSISDN AND VV.MES=CONCAT(SUBSTRING(date_sub(ZZ.fecha_reporte, interval 1 month),1,4),SUBSTRING(date_sub(ZZ.fecha_reporte, interval 1 month),6,2)) )MM LEFT JOIN ( SELECT CUSTOMER_ID, CCNAME, CSCOMPREGNO FROM ( SELECT CUSTOMER_ID, CCNAME, CSCOMPREGNO, ROW_NUMBER() OVER(PARTITION BY CSCOMPREGNO ORDER BY CUSTOMER_ID DESC) FLAG FROM dwo.sa_ccontact_all WHERE CSCOMPREGNO IN ( SELECT NRO_DOCUMENTO FROM ( SELECT DISTINCT ZZ.IMEI, ZZ.msisdn, IF(ZZ.TIPO_DOCUMENTO IS NULL,VV.TIPO_DOCUMENTO,ZZ.TIPO_DOCUMENTO) TIPO_DOCUMENTO, IF(ZZ.NRO_DOCUMENTO IS NULL,VV.NRO_DOCUMENTO,ZZ.NRO_DOCUMENTO) NRO_DOCUMENTO FROM ( SELECT DISTINCT XX.IMEI, XX.fecha_reporte fecha_reporte, XX.msisdn, YY.TIPO_DOCUMENTO, YY.NRO_DOCUMENTO FROM ( SELECT DISTINCT AA.IMEI, BB.FECHA fecha_reporte, BB.msisdn FROM ( SELECT IMEI,estado_del_reporte,STR_TO_DATE(fecha_reporte, '%d/%m/%Y') fecha_reporte FROM dwo.reporte_sigrei_tmp ) AA LEFT JOIN dwo.ter_claro_movimiento BB ON AA.IMEI=BB.IMEI AND SUBSTRING(AA.fecha_reporte,1,10)=SUBSTRING(BB.FECHA,1,10) )XX LEFT JOIN DWO.F_M_ABONADOS YY on XX.MSISDN=YY.MSISDN AND YY.MES=CONCAT(SUBSTRING(XX.fecha_reporte,1,4),SUBSTRING(XX.fecha_reporte,6,2)) )ZZ LEFT JOIN DWO.F_M_ABONADOS VV on ZZ.MSISDN=VV.MSISDN AND VV.MES=CONCAT(SUBSTRING(date_sub(ZZ.fecha_reporte, interval 1 month),1,4),SUBSTRING(date_sub(ZZ.fecha_reporte, interval 1 month),6,2)) ) GG WHERE TIPO_DOCUMENTO='RUC' ) )S WHERE FLAG=1 )NN ON MM.NRO_DOCUMENTO=NN.CSCOMPREGNO")));
        */
    }

    public function headings(): array
    {
        return [
            'CODCLI',	'SERIE_RECIBO',	'NUM_RECIBO',	'TELEFONO_ORIGEN'	,'TELEFONO_DESTINO'	,'SERVICIO',	'NOMBRE_DESTINO',	'TIPO_DESTINO',	'HORAINI',	'HORAFIN',	'MINUTOS',	'SEGUNDOS',	'IDTIPHOR'	,'TARIFA',	'MONTO',	'NOMCLI', 'IDCON'	,'IDOPERADOR'	,'OPERADOR'	,'IDCLAISDEST'	,'CLASE_DESTINO'	,'IDGRPDES'	,'GRUPO_DESTINO',	'CANTIDADVAL'	,'CANTIDADORIGEN'	
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
            'K' => NumberFormat::FORMAT_NUMBER_00,
            'L' => NumberFormat::FORMAT_NUMBER_00,
            'N' => NumberFormat::FORMAT_NUMBER_00,
            'O' => NumberFormat::FORMAT_NUMBER_00,
            'Q' => NumberFormat::FORMAT_NUMBER_00,
            'X' => NumberFormat::FORMAT_NUMBER_00,
            'Y' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $count = count($this->collection());
        
        //$index = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($count+1);

        $sheet->getStyle("A2:Y".$count+1)->applyFromArray([
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

            'A1:Z1' => [
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
        $result = DB::connection('oracle')->select(DB::RAW("select telefono_origen from USRAES.TB_FIJA_FACTURADA where rownum = 1"));
        if(isset($result[0])){
            return 'TEF FIJO '.$result[0]->telefono_origen;
        }else{
            return 'TEF FIJO';
        }
        
    }
}