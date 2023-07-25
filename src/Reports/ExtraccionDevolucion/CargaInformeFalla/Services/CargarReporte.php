<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Mail\NotificacionCarga;
use Illuminate\Support\Facades\Mail;

class CargarReporte
{
    private $repo;
    
    public function __construct(ExtraccionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($numero, $excel)
    {
        $reporte = $this->repo->updateReporte($numero, $excel->getClientOriginalName());
        if($reporte){
            $excel->storeAs('carga_informe_falla', $numero.'_'.$excel->getClientOriginalName());
            $reportes = $this->repo->getReportesSnRevisado();
            $correo = new NotificacionCarga($reportes);
            
            try {
                // Envío del correo
                Mail::to(['C19884@claro.com.pe','C26282@claro.com.pe','ellanos@indracompany.com','lizeth.moya@claro.com.pe','carlos.malpartida@claro.com.pe'])->send($correo);
                //Mail::to(['ellanos@indracompany.com'])->send($correo);
            } catch (\Exception $e) {
                // Captura cualquier excepción generada durante el envío del correo
                return response()->json(['message' => 'Error al enviar el correo: '.$e->getMessage()], 500);
            }
        }
        return $reporte;
    }

    private function getDataFromExcel($excel)
    {
        $values = [];
        if ($excel !== null) {
            $reader = IOFactory::createReader('Xlsx');
            $spreedsheet = $reader->load($excel->getPathname());
            $sheet = $spreedsheet->getSheet(1);
            $highestRow = $sheet->getHighestRow();
            for ($i=2; $i <= $highestRow; $i++) {
                $row = [];
                $row["ticket"] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
                $row["msisdn"] = $sheet->getCellByColumnAndRow(2, $i)->getValue();
                // $row["mto_dev_facturacion"] = $sheet->getCellByColumnAndRow(19, $i)->getOldCalculatedValue();
                $row["mto_dev_facturacion"] = $sheet->getCellByColumnAndRow(19, $i)->getValue();
                if(str_starts_with($row["mto_dev_facturacion"], "=")){
                    $row["mto_dev_facturacion"] = $sheet->getCellByColumnAndRow(19, $i)->getOldCalculatedValue();
                }
                $row["mto_dev"] = $sheet->getCellByColumnAndRow(20, $i)->getValue();
                if(str_starts_with($row["mto_dev"], "=")){
                    $row["mto_dev"] = $sheet->getCellByColumnAndRow(20, $i)->getOldCalculatedValue();
                }
                $row["factura_aplicada"] = $sheet->getCellByColumnAndRow(21, $i)->getValue();
                $row["fecha_devolucion"] = $this->formatExcelDate($sheet->getCellByColumnAndRow(22, $i)->getValue());
                $row["fecha_registro_devolucion"] = $this->formatExcelDate($sheet->getCellByColumnAndRow(23, $i)->getValue());
                $row["observacion"] = $sheet->getCellByColumnAndRow(24, $i)->getValue();
                $values[] = $row;
            }
        }
        return $values;
    }

    private function formatExcelDate($strDate)
    {
        $output = null;
        if(is_numeric($strDate)){
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($strDate);
            $output = $date->format("Y-m-d")." 00:00:00";
        }else{
            $date = DateTime::createFromFormat('d/m/Y', $strDate);
            if($date){
                $output = $date->format("Y-m-d")." 00:00:00";
            }
        }
        return $output;
    }

    private function getDataFromLog($file)
    {
        $values = [];
        if ($file !== null) {
            // $file_contents = file_get_contents($file->getPathname());
            $file = fopen($file->getPathname(), "r");
            $row = [];
            $count = 1;
            while(!feof($file)){
                $line = fgets($file);
                if(str_contains($line, "#@TRANSACTION")){
                    $row = [];
                }else if(str_contains($line, "#@END_TRANSACTION")){
                    $values[] = $row;
                }else{
                    $line_values = [];
                    $output = str_replace([
                        "#CA::Modify:PackageItem(",
                        "CA::Modify:PackageItem(",
                        ");",
                        "=\"",
                        "\",",
                        ",",
                        "\n",
                        "\""
                    ], ["","","","=",",","&", "", ""], $line);
                    $output = str_replace(["#CA::Modify:CustomerLifeCycleState(", "CA::Modify:CustomerLifeCycleState("], ["",""], $output);
                    parse_str($output, $line_values);
                    $row = array_merge($row, $line_values);
                }
                // $count++;
                // if($count > 20){
                //     break;
                // }
            }
            fclose($file);
        }
        return $values;
    }
}
