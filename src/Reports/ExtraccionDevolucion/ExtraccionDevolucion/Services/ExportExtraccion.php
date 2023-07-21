<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\ExtraccionRepository;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\TipoInput;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Ramsey\Uuid\Uuid;

class ExportExtraccion
{
    private $repo;
    private $exportService;
    private $saveReportLog;
    public function __construct(ExtraccionRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke($tipoInput, $celdas, $provincias, $excel, $fechaIni, $fechaFin, $ticketOsiptel, $fechaInteres, $corteFechaIni, $corteFechaFin)
    {
        $dtStart = new DateTime();
        try {
            $dtFechaIni = DateTime::createFromFormat("Y-m-d H:i:s", $fechaIni);
            $dtFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", $fechaFin);
            $dtFechaInteres = new DateTime();
            $dtFechaInteres->modify("+{$fechaInteres} month");
            $dtCorteFechaIni = DateTime::createFromFormat("Y-m-d H:i:s", $corteFechaIni);
            $dtCorteFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", $corteFechaFin);

            $arrayCeldas = [];
            $arrayProvincias = [];

            switch ($tipoInput) {
                case TipoInput::MANUAL:
                    $arrayCeldas = explode(",", trim($celdas));

                    $provincias = explode("\n", str_replace("\r", "", trim($provincias)));
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

            if(!$dtFechaIni){
                throw new Exception("La fecha y hora de inicio no son válidos");
            }
            if(!$dtFechaFin){
                throw new Exception("La fecha y hora fin no son válidos");
            }
            if(!$dtFechaInteres){
                throw new Exception("La fecha de interes no es válida");
            }

            $data = $this->repo->getReporte($arrayCeldas, $arrayProvincias, $dtFechaIni, $dtFechaFin, $ticketOsiptel, $dtFechaInteres, $dtCorteFechaIni, $dtCorteFechaFin);

            $options = [
                "sheetIndex" => 0,
                'title' => "REPORTE",
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
                "item" => ["label" => "ITEM"],
                "ticket" => ["label" => "TICKET"],
                "id_cliente" => ["label" => "ID_CLIENTE"],
                "nro_documento" => ["label" => "NRO_DOCUMENTO"],
                "nombres_apellidos" => ["label" => "NOMBRES_APELLIDOS"],
                "servicio_afectado" => ["label" => "SERVICIO_AFECTADO"],
                "msisdn" => ["label" => "MSISDN"],
                "departamento" => ["label" => "DEPARTAMENTO"],
            ];
            $this->exportService->loadData($headers, $data, $options);

            $content = $this->exportService->getWriter(WriterType::XLSX)->getOutput();

            $tempFilename = storage_path('app/public')."/".Uuid::uuid4()->toString().".xlsx";
            $fp = fopen($tempFilename, "w");
            fwrite($fp, $content);
            fclose($fp);

            $this->reportLog($tempFilename, $dtStart, new DateTime());
            unlink($tempFilename);

            return new Response([], [
                "filename" => "Base_Abonados_Movil_TK{$ticketOsiptel}.xlsx",
                "type" => "xlsx",
                "content" => $content,
            ]);
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
                $row[] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
                $row[] = $sheet->getCellByColumnAndRow(2, $i)->getValue();
                $row[] = $sheet->getCellByColumnAndRow(3, $i)->getValue();
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
