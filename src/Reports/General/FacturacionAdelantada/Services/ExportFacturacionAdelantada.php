<?php

namespace AMovil\Reports\General\FacturacionAdelantada\Services;

use AMovil\Reports\General\FacturacionAdelantada\Domain\FacturacionAdelantadaRepository;
use AMovil\Reports\ReportLog\Domain\ReportLogStatus;
use AMovil\Reports\ReportLog\Services\SaveReportDto;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Throwable;

class ExportFacturacionAdelantada
{
    private $repo;
    private $exportService;
    private $saveReportLog;

    public function __construct(FacturacionAdelantadaRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke($tipoInput, $periodo, $fechaIni, $fechaFin, $cuenta, $numeroFactura)
    {
        $fechaIniExec = new DateTime();
        $dtFechaIni = null;
        $dtFechaFin = null;
        $data = [];
        $reporteInput = ['tipoInput' => $tipoInput, 'cuenta' => $cuenta];

        try {
            if ($tipoInput === "1") {
                $dtPeriodo = DateTime::createFromFormat("Ym", $periodo);
                if($dtPeriodo === false){
                    throw new Exception("El periodo '{$periodo}' es invalido");
                }
                $reporteInput['periodo'] = $periodo;
                $data = $this->repo->getReporteByPeriodo($cuenta, $numeroFactura, $dtPeriodo);
            }else{
                $dtFechaIni = DateTime::createFromFormat("Y-m-d", $fechaIni);
                $dtFechaFin = DateTime::createFromFormat("Y-m-d", $fechaFin);
                $data = $this->repo->getReporteByDates($cuenta, $numeroFactura, $dtFechaIni, $dtFechaFin);
                $reporteInput['numeroFactura'] = $numeroFactura;
                $reporteInput['fechaInicio'] = $fechaIni;
                $reporteInput['fechaFin'] = $fechaFin;
            }
    
            $options = [
                "sheetIndex" => 0,
                'title' => "FACTURACION_ADELANTADA",
                'styles' => [
                    'header' => [
                        'font' => ['bold' => true, 'size' => 9],
                        'borders'=> [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => array('rgb'=>'000000')
                            ]
                        ]
                    ],
                    'body' => [
                        'font' => ['size' => 9]
                    ]
                ]
            ];
    
            $headers = [
                "cuenta" => ["label" => "CUENTA"],
                "ciclo" => ["label" => "CICLO"],
                "nro_factura" => ["label" => "NRO_FACTURA"],
                "nro_telef_orig" => ["label" => "NRO_TELEF_ORIG"],
                "fecha" => ["label" => "FECHA"],
                "hora_inicio" => ["label" => "HORA_INICIO"],
                "hora_fin" => ["label" => "HORA_FIN"],
                "pais" => ["label" => "PAIS"],
                "telef_destino" => ["label" => "TELEF_DESTINO"],
                "consumo" => ["label" => "CONSUMO"],
                "tipo_servicio" => ["label" => "TIPO_SERVICIO"],
                "destino" => ["label" => "DESTINO"],
                "operador" => ["label" => "OPERADOR"],
                "tipo_llamada" => ["label" => "TIPO_LLAMADA"],
                "cargo_final" => ["label" => "CARGO_FINAL"],
            ];
    
            $this->exportService->loadData($headers, $data, $options);
    
            $filename = "FACTURACION_ADELANTADA_".$fechaIniExec->format('YmdHis').".xlsx";
            $filePath = SaveReportLog::LOCAL_PATH."/FACTURACION_ADELANTADA/{$filename}";
    
            $this->exportService->getWriter(WriterType::XLSX)->save($filePath);
    
            $this->reportLog($filePath, $filename, $fechaIniExec, new DateTime(), $reporteInput, null);
            
            return new Response([], [
                "filename" => $filename,
                "type" => "xlsx",
                "content" => file_get_contents($filePath),
            ]);
        } catch (\Throwable $th) {
            $this->reportLog(null, null, $fechaIniExec, new DateTime(), $reporteInput, $th);
            throw $th;
        }
    }

    private function reportLog(?string $local_file, ?string $filename, DateTime $ini, DateTime $fin, array $input, ?Throwable $error)
    {
        $logDto = SaveReportDto::create(
            'FACTURACION_ADELANTADA',
            $filename,
            $ini,
            $fin,
            $error === null ? ReportLogStatus::CORRECTO : ReportLogStatus::ERROR,
            $error !== null ? $error->getMessage() : null,
            'detalle_consumo.facturacion',
            json_encode($input)
        );
        $this->saveReportLog->create($logDto);
        if ($local_file !== null) {
            $this->saveReportLog->sendFileToRemoteServer($local_file, "FACTURACION_ADELANTADA/{$filename}");
        }
    }
}
