<?php

namespace AMovil\Reports\RepDetConsumoNF\Services;

use AMovil\Reports\RepDetConsumoNF\Domain\DetalleConsumoNFRepository;
use AMovil\Reports\ReportLog\Domain\ReportLogStatus;
use AMovil\Reports\ReportLog\Services\SaveReportDto;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportConsumoDetalladoNF
{
    private $repo;
    private $exportService;
    private $saveReportLog;

    public function __construct(DetalleConsumoNFRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke(int $tipoInput, ?FileInput $excel, ?string $numCuenta, ?string $fechaIni, ?string $fechaFin): Response
    {
        $fechaIniExec = new DateTime();
        $reporteInput = ['tipoInput' => $tipoInput, 'fechaInicio' => $fechaIni, 'fechaFin' => $fechaFin];
        try {
            $dtFechaIni = DateTime::createFromFormat("Y-m-d", $fechaIni);
            $dtFechaFin = DateTime::createFromFormat("Y-m-d", $fechaFin);
            $data = [];
            if($tipoInput === 2){
                $reporteInput['numCuenta'] = $numCuenta;
                $data = $this->repo->getReporteDetallado($numCuenta, $dtFechaIni, $dtFechaFin);
            }else if ($tipoInput === 1){
                $lineas = $this->getDataFromExcel($excel);
                $reporteInput['lineas'] = $lineas;
                $data = $this->repo->getReporteDetalladoByLineas($lineas, $dtFechaIni, $dtFechaFin);
            }
            $filename = "REPORTE_CONSUMO_NF_DETALLADO_".$fechaIniExec->format('YmdHis').".xlsx";
            $tempfile = $this->generateReport($data);

            copy($tempfile, SaveReportLog::LOCAL_PATH."/DETALLE_CONSUMO/{$filename}");
            $this->reportLog($tempfile, $filename, $fechaIniExec, new DateTime(), $reporteInput, null);

            $content = file_get_contents($tempfile);
            unlink($tempfile);
            
            return Response::respData([
                "filename" => $filename,
                "content" => $content,
            ]);
        } catch (\Throwable $th) {
            $this->reportLog(null, null, $fechaIniExec, new DateTime(), $reporteInput, $th);
            throw $th;
        }
    }

    private function getDataFromExcel(FileInput $excel)
    {
        $values = [];
        if($excel->getExtension() !== "xlsx"){
            throw new Exception("Solo se permiten archivos xlsx");
        }
        $reader = IOFactory::createReader('Xlsx');
        $spreedsheet = $reader->load($excel->getFilePath());
        $sheet = $spreedsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        for ($i=1; $i <= $highestRow; $i++) {
            $values[] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
        }
        return $values;
    }

    public function generateReport($data){
        $options = [
            "sheetIndex" => 0,
            'title' => "DETALLE CONSUMO NF",
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
            "numero_cuenta_larga" => ["label" => "NUMERO_CUENTA_LARGA"],
            "ciclo" => ["label" => "CICLO"],
            "nro_factura" => ["label" => "NRO_FACTURA"],
            "nro_tel_origen" => ["label" => "NRO_TEL_ORIGEN"],
            "dia" => ["label" => "DIA"],
            "hora_inicio" => ["label" => "HORA_INICIO"],
            "hora_fin" => ["label" => "HORA_FIN"],
            "pais" => ["label" => "PAIS"],
            "nro_tel_destino" => ["label" => "NRO_TEL_DESTINO"],
            "consumo" => ["label" => "CONSUMO"],
            "tipo_servicio" => ["label" => "TIPO_SERVICIO"],
            "destino" => ["label" => "DESTINO"],
            "operador" => ["label" => "OPERADOR"],
            "tipo_llamada" => ["label" => "TIPO_LLAMADA"],
        ];

        $this->exportService->loadData($headers, $data, $options);
        $tempfile = $this->exportService->getWriter(WriterType::XLSX)->saveToTempfile();
        return $tempfile;
    }

    private function reportLog(?string $local_file, ?string $filename, DateTime $ini, DateTime $fin, array $input, ?\Throwable $error)
    {
        $logDto = SaveReportDto::create(
            'DETALLE_CONSUMO',
            $filename,
            $ini,
            $fin,
            $error === null ? ReportLogStatus::CORRECTO : ReportLogStatus::ERROR,
            $error !== null ? $error->getMessage() : null,
            'rep-det-consumo.nf.detallado',
            json_encode($input)
        );
        $this->saveReportLog->create($logDto);
        if ($local_file !== null) {
            $this->saveReportLog->sendFileToRemoteServer($local_file, "DETALLE_CONSUMO/{$filename}");
        }
    }
}
