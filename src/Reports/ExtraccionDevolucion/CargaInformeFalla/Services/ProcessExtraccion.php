<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\ExtraccionRepository;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\TipoInput;
use AMovil\Reports\ExtraccionDevolucion\TablaInteres\Domain\TablaInteresRepository;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessExtraccion
{
    private $repo;
    private $saveReportLog;
    private $tablaInteresRepo;
    
    public function __construct(ExtraccionRepository $repo, SaveReportLog $saveReportLog, TablaInteresRepository $tablaInteresRepo)
    {
        $this->repo = $repo;
        $this->saveReportLog = $saveReportLog;
        $this->tablaInteresRepo = $tablaInteresRepo;
    }

    public function __invoke(?int $step, $tipoInput, $celdas, $provincias, $excel, $fechaIni, $fechaFin, $ticketOsiptel, $fechaInteres, $corteFechaIni, $corteFechaFin, $minutos_usuarios)
    {
        $dtStart = new DateTime();
        try {
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
