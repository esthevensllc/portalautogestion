<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\ExtraccionRepository as ExtraccionRepository1;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository as ExtraccionRepository2;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\InformeTipoReporte;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\GetDepartamentosByNumReporte;
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
    private $sendFilePrepago;
    private $departamentosPendientesGetter;

    const MESES_INTERES = 24;
    const MINUTOS_USUARIOS = 3;

    public function __construct(
        ExtraccionRepository1 $repo,
        ExtraccionRepository2 $repo2,
        SaveReportLog $saveReportLog,
        TablaInteresRepository $tablaInteresRepo,
        SendFilePrepagoProcesadoEvent $sendFilePrepago,
        GetDepartamentosByNumReporte $departamentosPendientesGetter
    ) {
        $this->repo = $repo;
        $this->repo2 = $repo2;
        $this->saveReportLog = $saveReportLog;
        $this->tablaInteresRepo = $tablaInteresRepo;
        $this->sendFilePrepago = $sendFilePrepago;
        $this->departamentosPendientesGetter = $departamentosPendientesGetter;
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
            
            $dtLastDay = new DateTime();
            $dtLastDay->modify("-1 day");
            if (!$this->tablaInteresRepo->existsIn($dtLastDay)) {
                throw new Exception("La tabla de interes no esta actualizada");
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
                throw new Exception("El ticket ya se proceso para el departamento {$depatamento}");
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

            if (!$this->tablaInteresRepo->existsIn($dtFechaInteres) && !$this->tablaInteresRepo->existsIn($dtCorteFechaIni)) {
                throw new Exception("La tabla de interes no esta actualizada");
            }

            if($step === 1){
                $informe = $this->repo2->getInputByTicket($ticketOsiptel);
                $usuariosAfectados = null;
                if($informe !== null && (int) $informe->tipo_reporte === InformeTipoReporte::BY_MSISDN){
                    $usuariosAfectados = $this->repo->getReporteWithoutValidation($arrayCeldas, $arrayProvincias, $dtFechaIni, $dtFechaFin, $ticketOsiptel, $dtFechaInteres, $dtCorteFechaIni, $dtCorteFechaFin);
                } else {
                    $usuariosAfectados = $this->repo->getReporte($arrayCeldas, $arrayProvincias, $dtFechaIni, $dtFechaFin, $ticketOsiptel, $dtFechaInteres, $dtCorteFechaIni, $dtCorteFechaFin);
                }

                return new Response([], $usuariosAfectados);
            }else{
                $data = $this->repo->getReporte2($arrayCeldas, $arrayProvincias, $dtFechaIni, $dtFechaFin, $ticketOsiptel, $dtFechaInteres, $dtCorteFechaIni, $dtCorteFechaFin);

                $this->repo->getReporteMontoDevolver($dtFechaInteres, $dtCorteFechaIni);
            }

            $departamentosPendientes = $this->departamentosPendientesGetter->__invoke($inforFalla->numero_de_reporte)->data();
            if(count($departamentosPendientes) === 0){
                $this->repo2->procesado($inforFalla->numero_de_reporte);
            }

            // $reportes = $this->repo2->getReportesProcesados();
            $correo = new NotificacionProcesado($ticketOsiptel, $depatamento);
            $correo->setSubject("PROCESADO - TK {$ticketOsiptel} - {$inforFalla->name_file}");
            // Envío del correo
            Mail::to([
                'C19884@claro.com.pe',
                'jose.ramosm@claro.com.pe',
                'soporteprepagofactory@claro.com.pe',
                'edward.granados@claro.com.pe',
                'ayskel.guevara@claro.com.pe',
                'elver.ramirez@claro.com.pe',
                'alex.leguia@claro.com.pe',
                'michael.lazaro@claro.com.pe',
                'factsopfacturacion@claro.com.pe',
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
                'cdiazb@claro.com.pe',
                'C26670@claro.com.pe',
                'rhurtado@claro.com.pe',
                'angela.felix@claro.com.pe',
                'luis.huatuco@claro.com.pe',
                'david.granados@claro.com.pe',
                'c26977@claro.com.pe',
                'C26131@claro.com.pe',
                'C27727@claro.com.pe',
                'C27689@claro.com.pe',
                'fabiola.sanchez@claro.com.pe',
            ])->send($correo);

            $this->sendFilePrepago->__invoke($ticketOsiptel, $depatamento);

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
