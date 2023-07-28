<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\ExtraccionRepository as ExtraccionRepository1;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository as ExtraccionRepository2;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\TipoInput;
use AMovil\Reports\ExtraccionDevolucion\TablaInteres\Domain\TablaInteresRepository;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Mail\NotificacionProcesado;
use Illuminate\Support\Facades\Mail;

class ProcessExtraccion
{
    private $repo;
    private $repo2;
    private $saveReportLog;
    private $tablaInteresRepo;
    
    public function __construct(ExtraccionRepository1 $repo, ExtraccionRepository2 $repo2,SaveReportLog $saveReportLog, TablaInteresRepository $tablaInteresRepo)
    {
        $this->repo = $repo;
        $this->repo2 = $repo2;
        $this->saveReportLog = $saveReportLog;
        $this->tablaInteresRepo = $tablaInteresRepo;
    }

    public function __invoke(?int $step, $tipoInput, $celdas, $provincias, $excel, $fechaIni, $fechaFin, $ticketOsiptel, $fechaInteres, $corteFechaIni, $corteFechaFin, $minutos_usuarios)
    {
        $dtStart = new DateTime();
        $inforFalla = $this->repo2->findInformeByTicket($ticketOsiptel);
        try {
            if($inforFalla === null){
                throw new Exception("No se puede procesar el reporte debido a que no existe un informe de fallas asociado");
            }
            $strMaxFechaInteres = DateTime::createFromFormat("Y-m-d H:i:s", $this->tablaInteresRepo->getMaxFechaInteres())->format("Ymd");
            $strMinFechaInteres = (clone $dtStart)->modify("-1 day")->format("Ymd");
            if($strMaxFechaInteres < $strMinFechaInteres){
                throw new Exception("No es posible procesar porque la tabla de interes no esta actualizada");
            }
            // $dtFechaIni = DateTime::createFromFormat("Y-m-d H:i:s", $fechaIni);
            // $dtFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", $fechaFin);
            $dtFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", $corteFechaIni);
            $dtFechaIni = (clone $dtFechaFin)->modify("-{$minutos_usuarios} minute");

            $dtFechaInteres = new DateTime();
            $dtFechaInteres->modify("+{$fechaInteres} month");
            $dtCorteFechaIni = DateTime::createFromFormat("Y-m-d H:i:s", $corteFechaIni);
            $dtCorteFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", $corteFechaFin);
            // dd([$dtFechaIni, $dtFechaFin]);

            $arrayCeldas = [];
            $arrayProvincias = [];
            
            switch ($tipoInput) {
                case TipoInput::MANUAL:
                    $arrayCeldas = explode(",", trim($celdas));

                    $provincias = explode("\n", str_replace("\r", "", strtoupper(trim($provincias))));
                    foreach($provincias as $row){
                        $arrayProvincias[] = explode(",", $row);
                    }
                    break;
                case TipoInput::EXCEL:
                    $excelData = $this->getDataFromExcel($excel);
                    $arrayCeldas = $excelData["celdas"];
                    $arrayProvincias = $excelData["provincias"];
                    break;
                default:
                    break;
            }

            if(count($provincias) === 0){
                throw new Exception("El departamento es obligatorio");
            }

            $depatamento = $arrayProvincias[0][0];
            $exists = $this->repo->ticketAndDepartamentoExistsInConsolidado($ticketOsiptel, $depatamento);
            
            if($exists){
                throw new Exception("El ticket ya se proceso");
            }

            if(!$dtFechaIni){
                throw new Exception("La fecha y hora de inicio no son válidos");
            }
            if(!$dtFechaFin){
                throw new Exception("La fecha y hora fin no son válidos");
            }
            if(!$dtFechaInteres){
                throw new Exception("La fecha de interes no es válida");
            }

            if($step === 1){
                $usuariosAfectados = $this->repo->getReporte($arrayCeldas, $arrayProvincias, $dtFechaIni, $dtFechaFin, $ticketOsiptel, $dtFechaInteres, $dtCorteFechaIni, $dtCorteFechaFin);

                return new Response([], $usuariosAfectados);
            }else{
                $data = $this->repo->getReporte2($arrayCeldas, $arrayProvincias, $dtFechaIni, $dtFechaFin, $ticketOsiptel, $dtFechaInteres, $dtCorteFechaIni, $dtCorteFechaFin);

                $this->repo->getReporteMontoDevolver($dtFechaInteres, $dtCorteFechaIni);
            }

            $this->repo2->procesado($inforFalla->numero_de_reporte);
            // $reportes = $this->repo2->getReportesProcesados();
            $correo = new NotificacionProcesado($ticketOsiptel, $depatamento);
            $correo->setSubject("PROCESADO - TK {$ticketOsiptel} - {$inforFalla->name_file}");
            
            //try {
                // Envío del correo
                Mail::to([
                    'C19884@claro.com.pe',
                    'C26282@claro.com.pe',
                    'ellanos@indracompany.com',
                    'cclinarez@indracompany.com',
                    'C25976@claro.com.pe',
                    'josias.luna@claro.com.pe',
                    'omori@claro.com.pe',
                    'cpalacios@claro.com.pe',
                    'luyciana.rodriguez@claro.com.pe',
                    'C26311@claro.com.pe',
                    'marali.huaranca@claro.com.pe',
                    'pleon@claro.com.pe',
                    'C26559@claro.com.pe',
                    'carlos.malpartida@claro.com.pe',
                    'lizeth.moya@claro.com.pe',
                    'bryan.robles@claro.com.pe',
                ])->send($correo);
                // Mail::to(['C26282@claro.com.pe'])->send($correo);
            /*} catch (\Exception $e) {
                // Captura cualquier excepción generada durante el envío del correo
                return response()->json(['message' => 'Error al enviar el correo: '.$e->getMessage()], 500);
            }*/

            $this->reportLog(null, $dtStart, new DateTime(), ["estado" => 1]);

            return new Response([], null);
        } catch (\Throwable $th) {
            $this->reportLog(null, $dtStart, new DateTime(), ['mensaje' => $th->getMessage()]);
            throw $th;
        }
        
    }

    private function getDataFromExcel($excel)
    {
        $values = ["celdas" => [], "provincias" => []];
        if ($excel !== null) {
            $reader = IOFactory::createReader('Xlsx');
            $spreedsheet = $reader->load($excel->getPathname());
            $sheet = $spreedsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            for ($i=1; $i <= $highestRow; $i++) {
                $row = [];
                $values["celdas"][] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
            }

            $sheet = $spreedsheet->getSheet(1);
            $highestRow = $sheet->getHighestRow();
            for ($i=1; $i <= $highestRow; $i++) {
                $row = [];
                $row[] = strtoupper($sheet->getCellByColumnAndRow(1, $i)->getValue());
                $row[] = strtoupper($sheet->getCellByColumnAndRow(2, $i)->getValue());
                $row[] = strtoupper($sheet->getCellByColumnAndRow(3, $i)->getValue());
                $values["provincias"][] = $row;
            }
        }
        return $values;
    }

    private function reportLog($allFilename, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        $filename = "EXTRACCION_".$ini->format('YmdHis').".xlsx";
        $data = array_merge([
            'name' => 'EXTRACCION',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => 'extraccion-devolucion',
            "filename" => $allFilename !== null ? $filename : null
        ], $extra_data);
        $this->saveReportLog->__invoke($data, $allFilename, 'EXTRACCION');
    }
}
