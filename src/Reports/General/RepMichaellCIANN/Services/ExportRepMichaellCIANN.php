<?php

namespace AMovil\Reports\General\RepMichaellCIANN\Services;

use AMovil\Reports\General\RepMichaellCIANN\Domain\RepMichaellCIANNRepository;
use AMovil\Reports\ReportLog\Domain\ReportLogStatus;
use AMovil\Reports\ReportLog\Services\SaveReportDto;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;
use Ramsey\Uuid\Uuid;

class ExportRepMichaellCIANN
{
    private $repo;
    private $exportService;
    private $saveReportLog;
    private $storagePath;

    public function __construct(RepMichaellCIANNRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
        $this->storagePath = storage_path('app/public');
    }

    public function __invoke($numCuenta, $periodo)
    {
        $dt_start = new DateTime();
        $reporteInput = ["numCuenta" => $numCuenta, "periodo" => $periodo];
        try {
            $dtPeriodo = DateTime::createFromFormat("Ym", $periodo);

            $options = [
                "sheetIndex" => -1,
                'title' => "",
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

            $numberFormat1 = ['numberFormat' => ['formatCode' => SpreadsheetStyle\NumberFormat::FORMAT_NUMBER]];
            $numberFormat2 = ['numberFormat' => ['formatCode' => SpreadsheetStyle\NumberFormat::FORMAT_NUMBER_00]];

            $headers = [
                "invoicenumber" => ["label" => "INVOICENUMBER"],
                "msisdn" => ["label" => "MSISDN"],
                "secuencial" => ["label" => "SECUENCIAL"],
                "runningmainnumber" => ["label" => "RUNNINGMAINNUMBER"],
                "calldate" => ["label" => "CALLDATE"],
                "calltime" => ["label" => "CALLTIME"],
                "callorigin" => ["label" => "CALLORIGIN"],
                "callnumber" => ["label" => "CALLNUMBER", 'bodyStyles' => $numberFormat1],
                "calldestination" => ["label" => "CALLDESTINATION"],
                "callduration" => ["label" => "CALLDURATION"],
                "callairtime" => ["label" => "CALLAIRTIME", 'bodyStyles' => $numberFormat2],
                "callland" => ["label" => "CALLLAND", 'bodyStyles' => $numberFormat2],
                "calltotal" => ["label" => "CALLTOTAL", 'bodyStyles' => $numberFormat2],
                "calltype" => ["label" => "CALLTYPE"],
                "tariffzone" => ["label" => "TARIFFZONE"],
                "tarifftime" => ["label" => "TARIFFTIME"],
                "rtxtype" => ["label" => "RTXTYPE"],
                "groupbox" => ["label" => "GROUPBOX"],
                "tipollamada" => ["label" => "TIPOLLAMADA"]
            ];
            $options["sheetIndex"]++;
            $options["title"] = "LLAMADAS";
            $data = $this->repo->getLlamadasByNumCuenta_Periodo($numCuenta, $dtPeriodo);
            $this->exportService->loadData($headers, $data, $options);
            $this->setColumnAutoSize($options["sheetIndex"]);

            $headers = [
                "invoicenumber" => ["label" => "INVOICENUMBER"],
                "msisdn" => ["label" => "MSISDN"],
                "secuencial" => ["label" => "SECUENCIAL"],
                "runningmainnumber" => ["label" => "RUNNINGMAINNUMBER"],
                "smsdate" => ["label" => "SMSDATE"],
                "smstime" => ["label" => "SMSTIME"],
                "smsorigin" => ["label" => "SMSORIGIN"],
                "smsnumber" => ["label" => "SMSNUMBER"],
                "smsdestination" => ["label" => "SMSDESTINATION"],
                "smsduration" => ["label" => "SMSDURATION"],
                "smsairtime" => ["label" => "SMSAIRTIME", 'bodyStyles' => $numberFormat2],
                "smsland" => ["label" => "SMSLAND", 'bodyStyles' => $numberFormat2],
                "smstotal" => ["label" => "SMSTOTAL", 'bodyStyles' => $numberFormat2],
                "smstype" => ["label" => "SMSTYPE"],
                "tariffzone" => ["label" => "TARIFFZONE"],
                "tarifftime" => ["label" => "TARIFFTIME"],
                "rtxtype" => ["label" => "RTXTYPE"],
            ];
            $options["sheetIndex"]++;
            $options["title"] = "SVA";
            $data = $this->repo->getSVAByNumCuenta_Periodo($numCuenta, $dtPeriodo);
            $this->exportService->loadData($headers, $data, $options);
            $this->setColumnAutoSize($options["sheetIndex"]);

            $headers = [
                "invoicenumber" => ["label" => "INVOICENUMBER"],
                "secuencial" => ["label" => "SECUENCIAL"],
                "fromdate" => ["label" => "FROMDATE"],
                "todate" => ["label" => "TODATE"],
                "amount" => ["label" => "AMOUNT", 'bodyStyles' => $numberFormat2],
                "quantity" => ["label" => "QUANTITY"],
                "rateplan" => ["label" => "RATEPLAN"],
                "freeusage1" => ["label" => "FREEUSAGE1"],
                "freeusage2" => ["label" => "FREEUSAGE2"],
                "groupbox" => ["label" => "GROUPBOX"],
            ];
            $options["sheetIndex"]++;
            $options["title"] = "CUOTAS";
            $data = $this->repo->getCuotasByNumCuenta_Periodo($numCuenta, $dtPeriodo);
            $this->exportService->loadData($headers, $data, $options);
            $this->setColumnAutoSize($options["sheetIndex"]);

            $headers = [
                "invoicenumber" => ["label" => "INVOICENUMBER"],
                "msisdn" => ["label" => "MSISDN"],
                "secuencial" => ["label" => "SECUENCIAL"],
                "description" => ["label" => "DESCRIPTION"],
                "fromdate" => ["label" => "FROMDATE"],
                "todate" => ["label" => "TODATE"],
                "amount" => ["label" => "AMOUNT", 'bodyStyles' => $numberFormat2],
                "rateplan" => ["label" => "RATEPLAN"],
            ];
            $options["sheetIndex"]++;
            $options["title"] = "REPORTE SERVICIOS";
            $data = $this->repo->getReporteServiciosByNumCuenta_Periodo($numCuenta, $dtPeriodo);
            $this->exportService->loadData($headers, $data, $options);
            $this->setColumnAutoSize($options["sheetIndex"]);

            $headers = [
                "invoicenumber" => ["label" => "INVOICENUMBER"],
                "secuencial" => ["label" => "SECUENCIAL"],
                "description" => ["label" => "DESCRIPTION"],
                "reason" => ["label" => "REASON"],
                "amount" => ["label" => "AMOUNT", 'bodyStyles' => $numberFormat2],
                "group_box" => ["label" => "GROUP_BOX"],
            ];
            $options["sheetIndex"]++;
            $options["title"] = "REPORTE OCC";
            $data = $this->repo->getReporteOCCByNumCuenta_Periodo($numCuenta, $dtPeriodo);
            $this->exportService->loadData($headers, $data, $options);
            $this->setColumnAutoSize($options["sheetIndex"]);

            $headers = [
                "invoicenumber" => ["label" => "INVOICENUMBER"],
                "msisdn" => ["label" => "MSISDN"],
                "secuencial" => ["label" => "SECUENCIAL"],
                "runningmainnumber" => ["label" => "RUNNINGMAINNUMBER"],
                "calldate" => ["label" => "CALLDATE"],
                "calltime" => ["label" => "CALLTIME"],
                "callorigin" => ["label" => "CALLORIGIN"],
                "callnumber" => ["label" => "CALLNUMBER"],
                "calldestination" => ["label" => "CALLDESTINATION"],
                "callduration" => ["label" => "CALLDURATION"],
                "callairtime" => ["label" => "CALLAIRTIME", 'bodyStyles' => $numberFormat2],
                "callland" => ["label" => "CALLLAND", 'bodyStyles' => $numberFormat2],
                "calltotal" => ["label" => "CALLTOTAL", 'bodyStyles' => $numberFormat2],
            ];
            $options["sheetIndex"]++;
            $options["title"] = "ROAMING";
            $data = $this->repo->getRoamingByNumCuenta_Periodo($numCuenta, $dtPeriodo);
            $this->exportService->loadData($headers, $data, $options);
            $this->setColumnAutoSize($options["sheetIndex"]);

            $headers = [
                "invoicenumber" => ["label" => "INVOICENUMBER"],
                "msisdn" => ["label" => "MSISDN"],
                "secuencial" => ["label" => "SECUENCIAL"],
                "description" => ["label" => "DESCRIPTION"],
                "fromdate" => ["label" => "FROMDATE"],
                "todate" => ["label" => "TODATE"],
                "amount" => ["label" => "AMOUNT", 'bodyStyles' => $numberFormat2],
                "rateplan" => ["label" => "RATEPLAN"],
                "freeusage1" => ["label" => "FREEUSAGE1"],
                "freeusage2" => ["label" => "FREEUSAGE2"],
                "freeusage3" => ["label" => "FREEUSAGE3"],
                "freeusage4" => ["label" => "FREEUSAGE4"],
                "groupbox" => ["label" => "GROUPBOX"],
                "freeusage5" => ["label" => "FREEUSAGE5"],
            ];
            $options["sheetIndex"]++;
            $options["title"] = "PLAN";
            $data = $this->repo->getPlanByNumCuenta_Periodo($numCuenta, $dtPeriodo);
            $this->exportService->loadData($headers, $data, $options);
            $this->setColumnAutoSize($options["sheetIndex"]);

            $filename = "MICHAELL_CIA_NN_".$dt_start->format('YmdHis');
            $filePath = SaveReportLog::LOCAL_PATH."/MICHAELL_CIA_NN/{$filename}";
            $this->exportService->getWriter(WriterType::XLSX)->save($filePath);

            $this->reportLog($filePath, $filename, $dt_start, new DateTime(), $reporteInput, null);

            $response = new Response([], [
                'content' => file_get_contents($filePath),
                'type' => 'xlsx',
                'filename' => "MICHAELL_CIA_NN_{$periodo}.xlsx"
            ]);
            return $response;
        } catch (\Throwable $th) {
            $this->reportLog(null, null, $dt_start, new DateTime(), $reporteInput, null);
            throw $th;
        }
    }

    private function setColumnAutoSize($sheetIndex)
    {
        $this->exportService->getExportReference()->setActiveSheetIndex($sheetIndex);
        $sheet = $this->exportService->getExportReference()->getActiveSheet();
        $columns_to_autosize = ['A','B','C','D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q'];
        foreach($columns_to_autosize as $col){
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function reportLog(?string $local_file, ?string $filename, DateTime $ini, DateTime $fin, array $input, ?\Throwable $error)
    {
        $logDto = SaveReportDto::create(
            'MICHAELL_CIA_NN',
            $filename,
            $ini,
            $fin,
            $error === null ? ReportLogStatus::CORRECTO : ReportLogStatus::ERROR,
            $error !== null ? $error->getMessage() : null,
            'michaell-cia-nn',
            json_encode($input)
        );
        $this->saveReportLog->create($logDto);
        if ($local_file !== null) {
            $this->saveReportLog->sendFileToRemoteServer($local_file, "MICHAELL_CIA_NN/{$filename}");
        }
    }
}
