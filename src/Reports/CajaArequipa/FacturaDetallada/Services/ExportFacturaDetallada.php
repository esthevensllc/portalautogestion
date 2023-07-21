<?php

namespace AMovil\Reports\CajaArequipa\FacturaDetallada\Services;

use AMovil\Reports\CajaArequipa\FacturaDetallada\Domain\FacturaDetalladaRepository;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;

class ExportFacturaDetallada
{
    private $repo;
    private $exportService;
    private $saveReportLog;

    public function __construct(FacturaDetalladaRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke($numCuenta, $periodo)
    {
        $dt_start = new DateTime();
        try {
            $dt_periodo = DateTime::createFromFormat("Ym", $periodo);
            if(!$dt_periodo){
                throw new Exception("El periodo '{$periodo}' no es válido");
            }
            $cuotas = $this->repo->getCuotasByNumCuentaAndPeriodo($numCuenta, $dt_periodo);
            $servicios = $this->repo->getServiciosByNumCuentaAndPeriodo($numCuenta, $dt_periodo);
            $occ = $this->repo->getOCCByNumCuentaAndPeriodo($numCuenta, $dt_periodo);
            $roaming = $this->repo->getRoamingByNumCuentaAndPeriodo($numCuenta, $dt_periodo);
            $plan = $this->repo->getPlanByNumCuentaAndPeriodo($numCuenta, $dt_periodo);
            
            $options = [
                "sheetIndex" => 0,
                'title' => "{$periodo}",
                'styles' => [
                    'header' => [
                        'font' => ['bold' => true, 'size' => 9],
                    ],
                    'body' => [
                        'font' => ['size' => 9],
                    ]
                ]
            ];

            $headers = [
                "invoicenumber" => ["label" => "INVOICENUMBER"],
                "secuencial" => ["label" => "SECUENCIAL"],
                "fromdate" => ["label" => "FROMDATE"],
                "todate" => ["label" => "TODATE"],
                "amount" => ["label" => "AMOUNT"],
                "quantity" => ["label" => "QUANTITY"],
                "rateplan" => ["label" => "RATEPLAN"],
                "freeusage1" => ["label" => "FREEUSAGE1"],
                "freeusage2" => ["label" => "FREEUSAGE2"],
                "groupbox" => ["label" => "GROUPBOX"],
            ];
            $options["title"] = "CUOTAS";
            $this->exportService->loadData($headers, $cuotas, $options);

            $headers = [
                "invoicenumber" => ["label" => "INVOICENUMBER"],
                "msisdn" => ["label" => "MSISDN"],
                "secuencial" => ["label" => "SECUENCIAL"],
                "description" => ["label" => "DESCRIPTION"],
                "fromdate" => ["label" => "FROMDATE"],
                "todate" => ["label" => "TODATE"],
                "amount" => ["label" => "AMOUNT"],
                "rateplan" => ["label" => "AMOUNT"],
            ];
            $options["sheetIndex"]++;
            $options["title"] = "REPORTE SERVICIOS";
            $this->exportService->loadData($headers, $servicios, $options);

            $headers = [
                "invoicenumber" => ["label" => "INVOICENUMBER"],
                "secuencial" => ["label" => "SECUENCIAL"],
                "description" => ["label" => "DESCRIPTION"],
                "reason" => ["label" => "REASON"],
                "amount" => ["label" => "AMOUNT"],
                "group_box" => ["label" => "GROUP_BOX"],
            ];
            $options["sheetIndex"]++;
            $options["title"] = "OCC";
            $this->exportService->loadData($headers, $occ, $options);

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
                "callairtime" => ["label" => "CALLAIRTIME"],
                "callland" => ["label" => "CALLLAND"],
                "calltotal" => ["label" => "CALLTOTAL"],
            ];
            $options["sheetIndex"]++;
            $options["title"] = "ROAMING";
            $this->exportService->loadData($headers, $roaming, $options);
           
            $headers = [
                "invoicenumber" => ["label" => "INVOICENUMBER"],
                "msisdn" => ["label" => "MSISDN"],
                "secuencial" => ["label" => "SECUENCIAL"],
                "description" => ["label" => "DESCRIPTION"],
                "fromdate" => ["label" => "FROMDATE"],
                "todate" => ["label" => "TODATE"],
                "amount" => ["label" => "AMOUNT"],
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
            $this->exportService->loadData($headers, $plan, $options);
            
            $filename = storage_path("app/public/"."FACTURA_DETALLADA_".(new DateTime())->format('YmdHis').".xlsx");
            $this->exportService->getWriter(WriterType::XLSX)->save($filename);
            
            $this->reportLog($filename, $dt_start, new DateTime());

            return new Response([], [
                "filename" => "FACTURA_DETALLADA_".$dt_periodo->format("Ym").".xlsx",
                "content" => file_get_contents($filename)
            ]);
        } catch (\Throwable $th) {
            $this->reportLog(null, $dt_start, new DateTime(), ['mensaje' => $th->getMessage()]);
            throw $th;
        }
    }

    private function reportLog(?string $local_file, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        $filename = "FACTURA_DETALLADA_".$ini->format('YmdHis').".xlsx";
        $data = array_merge([
            'name' => 'CAJA_AREQUIPA',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => "caja-arequipa.factura-detallada",
            "filename" => $filename
        ], $extra_data);
        $this->saveReportLog->__invoke($data, $local_file, 'FACTURA_DETALLADA');
    }
}
