<?php

namespace AMovil\Reports\DetalleTasado\Services;

use AMovil\Reports\DetalleTasado\Domain\DetalleTasadoRepository;
use AMovil\Reports\ReportLog\Domain\ReportLogStatus;
use AMovil\Reports\ReportLog\Services\SaveReportDto;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportDetalleTasado
{
    private $repo;
    private $exportService;
    private $saveReportLog;

    public function __construct(DetalleTasadoRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke($numerosCuenta, $tipoInput, $fecha, $fechaIni, $fechaFin): Response
    {
        $fechaIniExec = new DateTime();
        $reporteInput = ['tipoInput' => $tipoInput, 'numerosCuenta' => $numerosCuenta];
        try {
            $dtFechaIni = null;
            $dtFechaFin = null;
            if($tipoInput === "1"){
                $dtFechaIni = DateTime::createFromFormat("Y-m-d", $fecha);
                $dtFechaFin = DateTime::createFromFormat("Y-m-d", $fecha);
            }else{
                $dtFechaIni = DateTime::createFromFormat("Y-m-d", $fechaIni);
                $dtFechaFin = DateTime::createFromFormat("Y-m-d", $fechaFin);
            }
            $reporteInput['fechaInicio'] = $dtFechaIni->format("Y-m-d");
            $reporteInput['fechaFin'] = $dtFechaFin->format("Y-m-d");

            $data = $this->repo->getReporte(explode(",", str_replace(" ", "", $numerosCuenta)), $dtFechaIni, $dtFechaFin);

            $options = [
                "sheetIndex" => 0,
                'title' => "DETALLE TASADO",
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
                "fch_trafico" => ["label" => "FCH_TRAFICO"],
                "msisdn" => ["label" => "MSISDN"],
                "plan" => ["label" => "PLAN"],
                "servicio_des" => ["label" => "SERVICIO_DES"],
                "trafico_bytes" => ["label" => "TRAFICO_BYTES"],
            ];

            $this->exportService->loadData($headers, $data, $options);

            $filename = "DETALLE_TASADO_".$fechaIniExec->format('YmdHis').".xlsx";
            $filePath = SaveReportLog::LOCAL_PATH."/DETALLE_TASADO/{$filename}";

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

    private function reportLog(?string $local_file, ?string $filename, DateTime $ini, DateTime $fin, array $input, ?\Throwable $error)
    {
        $logDto = SaveReportDto::create(
            'DETALLE_TASADO',
            $filename,
            $ini,
            $fin,
            $error === null ? ReportLogStatus::CORRECTO : ReportLogStatus::ERROR,
            $error !== null ? $error->getMessage() : null,
            'rep-det-consumo.detalle-tasado',
            json_encode($input)
        );
        $this->saveReportLog->create($logDto);
        if ($local_file !== null) {
            $this->saveReportLog->sendFileToRemoteServer($local_file, "DETALLE_TASADO/{$filename}");
        }
    }
}
