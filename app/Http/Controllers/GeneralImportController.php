<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use DB;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class GeneralImportController
{
    public function import_view(){
        return view('general_import.importar');
    }

    public function import(Request $request){
        ini_set('max_execution_time', '7200');
        set_time_limit(7200);

        $excel = $request->file('excel');
        $pathName = $request->file('excel')->getPathname();
        dd($pathName);
        $data = $this->getDataFromExcel($excel);
        // DB::connection("oracle_reptdm")->table('USRAES.BASE_TPI_BSCS_FIJA')->insert($data);
        // DB::table('usraes.padm_user_rol')->insert($roles);
        // DB::commit();
        return response()->json($data);
    }

    private function getDataFromExcel($excel)
    {
        $values = [];
        if ($excel !== null) {
            $reader = IOFactory::createReader('Csv');
            // $reader = IOFactory::createReader('Csv');
            //dd($excel);
            $spreedsheet = $reader->load($excel->getPathname());
            //$spreedsheet = $reader->load("/space/reportes/DAPU/TRAMITE_CONSULTA/input/REQ_OSIPTEL_DAPU-04060_02420_Con_Estado.csv");
            $sheet = $spreedsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            for ($i=2; $i <= $highestRow; $i++) {
                $row = [
                    "ticket" => $sheet->getCellByColumnAndRow(1, $i)->getValue(),
                    "nro_doc" => $sheet->getCellByColumnAndRow(2, $i)->getValue(),
                    "cod_cliente" => $sheet->getCellByColumnAndRow(3, $i)->getValue(),
                    "nro_servicio" => $sheet->getCellByColumnAndRow(4, $i)->getValue(),
                    "serv_analizado" => $sheet->getCellByColumnAndRow(5, $i)->getValue(),
                    "modalidad" => $sheet->getCellByColumnAndRow(6, $i)->getValue(),
                    "renta_mensual" => $sheet->getCellByColumnAndRow(7, $i)->getValue(),
                    "duracion_interrupcion" => $sheet->getCellByColumnAndRow(8, $i)->getValue(),
                    "monto_total_devuelto" => $sheet->getCellByColumnAndRow(9, $i)->getValue(),
                    "moneda" => $sheet->getCellByColumnAndRow(10, $i)->getValue(),
                    "fecha_dev" => $sheet->getCellByColumnAndRow(11, $i)->getValue(),
                    "recibo_dev" => $sheet->getCellByColumnAndRow(12, $i)->getValue(),
                    "estado" => $sheet->getCellByColumnAndRow(13, $i)->getValue(),
                    "fecha_baja" => $sheet->getCellByColumnAndRow(14, $i)->getValue(),
                    "nombre" => $sheet->getCellByColumnAndRow(15, $i)->getValue(),
                    "lugar_cobrar" => $sheet->getCellByColumnAndRow(16, $i)->getValue(),
                    "requisitos_cobro" => $sheet->getCellByColumnAndRow(17, $i)->getValue(),
                    "comunicacion" => $sheet->getCellByColumnAndRow(18, $i)->getValue(),
                    "medio_comunicacion" => $sheet->getCellByColumnAndRow(19, $i)->getValue(),
                    "motivo" => $sheet->getCellByColumnAndRow(20, $i)->getValue(),
                    "comentarios" => $sheet->getCellByColumnAndRow(21, $i)->getValue(),
                    "fecha_reporte" => $sheet->getCellByColumnAndRow(22, $i)->getValue(),
                    "fecha_inicio" => $sheet->getCellByColumnAndRow(23, $i)->getValue(),
                    "fecha_fin" => $sheet->getCellByColumnAndRow(24, $i)->getValue(),
                    "interrupcion" => $sheet->getCellByColumnAndRow(25, $i)->getValue(),
                    "capital" => $sheet->getCellByColumnAndRow(26, $i)->getValue(),
                    "factor_devolucion" => $sheet->getCellByColumnAndRow(27, $i)->getValue(),
                    "factor_interrupcion" => $sheet->getCellByColumnAndRow(28, $i)->getValue(),
                    "monto_acumulado" => $sheet->getCellByColumnAndRow(29, $i)->getValue(),
                    "fuente" => $sheet->getCellByColumnAndRow(30, $i)->getValue(),
                    "monto_pendiente" => $sheet->getCellByColumnAndRow(31, $i)->getValue(),
                    "monto_devuelto_total" => $sheet->getCellByColumnAndRow(32, $i)->getValue(),
                    "plazo_para_devolver" => $sheet->getCellByColumnAndRow(33, $i)->getValue(),
                    "fecha_devolucion_act" => $sheet->getCellByColumnAndRow(34, $i)->getValue(),
                    "exceso_dias" => $sheet->getCellByColumnAndRow(35, $i)->getValue(),
                    "fecha_baja_act" => $sheet->getCellByColumnAndRow(36, $i)->getValue(),
                    "resultado_supervision" => $sheet->getCellByColumnAndRow(37, $i)->getValue(),
                    "comentarios_ac" => $sheet->getCellByColumnAndRow(38, $i)->getValue(),
                ];
                // $row['fecha_baja_udb'] = $this->formatExcelDate($row['fecha_baja_udb']);
                // DB::table('USRAES.Bajas_Pre_Sin_pdv')->insert($row);
                DB::connection("oracle_reptdm")->table('usraes.informacion_act')->insert($row);
                // $values[] = $row;
            }
        }
        return $values;
    }

    private function formatExcelDate($strDate)
    {
        $output = null;
        if(is_numeric($strDate)){
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($strDate);
            $output = $date->format("Y-m-d H:i:s");
        }else{
            $date = DateTime::createFromFormat('d/m/Y H:i:s', $strDate);
            $date2 = DateTime::createFromFormat('d/m/Y', $strDate);
            if($date){
                $output = $date->format("Y-m-d H:i:s");
            }elseif($date2){
                $output = $date2->format("Y-m-d H:i:s");
            }
        }
        return $output;
    }
}
