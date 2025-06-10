<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\InformeTipoReporte;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services\ProcessExtraccion;
use AMovil\Reports\ExtraccionDevolucion\TablaInteres\Domain\TablaInteresRepository;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Mail\NotificacionCarga;
use Exception;
use Illuminate\Support\Facades\Mail;

class CargarReporte
{
    private $repo;
    private $fechaInteresNumber = ProcessExtraccion::MESES_INTERES;
    private $minutosUsuarios = ProcessExtraccion::MINUTOS_USUARIOS;
    
    public function __construct(ExtraccionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($numero, $tipoReporte, $excel, $detalleExtraccion)
    {
        $this->validate($detalleExtraccion);
        $filename = $excel->getClientOriginalName();
        $reporte = $this->repo->updateReporte($numero, $excel->getClientOriginalName());
        if($reporte){
            foreach($detalleExtraccion as $row){
                $this->saveInputs(
                    $numero,
                    $tipoReporte,
                    explode(",", $row["celdas"]),
                    explode("\n", str_replace(["\t","\r"], ["",""], $row["distritos"])),
                    $row["corteFechaIni"],
                    $row["corteFechaFin"]
                );
            }

            $excel->storeAs('carga_informe_falla', $numero.'_'.$excel->getClientOriginalName());
            // $reportes = $this->repo->getReportesSnRevisado();
            $reportes = $this->repo->findInputFor($numero);
            if((int) $tipoReporte === InformeTipoReporte::BY_MSISDN){
                $this->repo->saveInputMsisdn($numero, $this->getMsisdnDataFromCsv($excel));
            }
            if((int) $tipoReporte === InformeTipoReporte::BY_MSISDN2){
                $this->repo->saveInputMsisdn($numero, $this->getMsisdn2DataFromCsv($detalleExtraccion[0]["corteFechaFin"], $excel));
            } else {
                $correo = new NotificacionCarga($reportes);
                $correo->setSubject("CARGA DE INFORMES DE FALLAS - {$filename}");
                
                try {
                    // Envío del correo
                    Mail::to([
                        'C19884@claro.com.pe',
                        'lizeth.moya@claro.com.pe',
                        'carlos.malpartida@claro.com.pe',
                        'bryan.robles@claro.com.pe',
                        'Noc-claro@claro.com.pe',
                        'cpalacios@claro.com.pe',
                        'cdiazb@claro.com.pe',
                    ])->send($correo);
                } catch (\Exception $e) {
                    // Captura cualquier excepción generada durante el envío del correo
                    return response()->json(['message' => 'Error al enviar el correo: '.$e->getMessage()], 500);
                }
            }
        }
        return $reporte;
    }

    private function validate($detalles){
        foreach($detalles as $row){
            $result = preg_match('/^[0-9,]+$/', $row["celdas"]);
            if($result === 0){
                throw new Exception("Las celdas solo pueden contener numeros o comas");
            }
        }
    }

    private function saveInputs($numero, $tipoReporte, $celdas, $provincias, $corteFechaIni, $corteFechaFin)
    {
        $arrayDistritos = [];
        foreach($provincias as $row){
            $arrayDistritos[] = explode(",", $row);
        }

        $dtFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", $corteFechaIni);
        $dtFechaIni = (clone $dtFechaFin)->modify("-{$this->minutosUsuarios} minute");

        $dtFechaInteres = new DateTime();
        $dtFechaInteres->modify("+{$this->fechaInteresNumber} month");
        $dtCorteFechaIni = DateTime::createFromFormat("Y-m-d H:i:s", $corteFechaIni);
        $dtCorteFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", $corteFechaFin);

        $this->repo->saveReportInputs(
            $numero,
            $tipoReporte,
            $celdas,
            $arrayDistritos,
            $dtFechaIni,
            $dtFechaFin,
            null,
            $dtFechaInteres,
            $dtCorteFechaIni,
            $dtCorteFechaFin
        );
    }

    public function getMsisdnDataFromCsv($file){
        $reader = IOFactory::createReader('Csv');
        $spreedsheet = $reader->load($file->getPathname());
        $sheet = $spreedsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $data = [];
        for ($i=2; $i <= $highestRow; $i++) {
            $row = [
                "ticket" => $sheet->getCellByColumnAndRow(1, $i)->getValue(),
                "msisdn" => $sheet->getCellByColumnAndRow(2, $i)->getValue(),
                "fecha_carga" => $sheet->getCellByColumnAndRow(3, $i)->getValue(),
                "celda" => $sheet->getCellByColumnAndRow(4, $i)->getValue(),
                "fecha_corte" => $sheet->getCellByColumnAndRow(5, $i)->getValue(),
                "departamento" => $sheet->getCellByColumnAndRow(6, $i)->getValue(),
                "provincia" => $sheet->getCellByColumnAndRow(7, $i)->getValue(),
                "distrito" => $sheet->getCellByColumnAndRow(8, $i)->getValue(),
                "comentario" => $sheet->getCellByColumnAndRow(9, $i)->getValue(),
            ];
            $row["fecha_carga"] = DateTime::createFromFormat("d/m/Y", $row["fecha_carga"]);
            $row["fecha_carga"] = $row["fecha_carga"] ? $row["fecha_carga"]->format("Y-m-d") : null;
            $row["fecha_corte"] = DateTime::createFromFormat("d/m/Y", $row["fecha_corte"]);
            $row["fecha_corte"] = $row["fecha_corte"] ? $row["fecha_corte"]->format("Y-m-d") : null;
            $data[] = $row;
        }
        return $data;
    }

    public function getMsisdn2DataFromCsv($fechaCorteFin, $file){
        $reader = IOFactory::createReader('Xlsx');
        $spreedsheet = $reader->load($file->getPathname());
        $sheet = $spreedsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $data = [];
        for ($i=2; $i <= $highestRow; $i++) {
            $now = new DateTime();
            $row = [
                "ticket" => null,
                "msisdn" => $sheet->getCellByColumnAndRow(1, $i)->getValue(),
                "fecha_carga" => $now,
                "celda" => null,
                "fecha_corte" => $fechaCorteFin,
                "departamento" => null,
                "provincia" => null,
                "distrito" => null,
                "comentario" => null,
                "cod_cliente" => $sheet->getCellByColumnAndRow(2, $i)->getValue(),
                "num_documento" => $sheet->getCellByColumnAndRow(3, $i)->getValue(),
                "nombres" => $sheet->getCellByColumnAndRow(4, $i)->getValue(),
                "apellidos" => $sheet->getCellByColumnAndRow(5, $i)->getValue(),
                "monto_dev_igv" => $sheet->getCellByColumnAndRow(6, $i)->getValue(),
            ];
            $data[] = $row;
        }
        return $data;
    }
}
