<?php

namespace AMovil\Reports\CajaArequipa\GBOtorgados\Services;

use AMovil\Reports\CajaArequipa\GBOtorgados\Domain\GBOtorgadosRepository;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportGBOtorgados
{
    private $repo;
    private $exportService;
    private $saveReportLog;
    public function __construct(GBOtorgadosRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke($numcuenta, $periodo)
    {
        $dt_start = new DateTime();
        try {
            $dt_periodo = DateTime::createFromFormat("Ym", $periodo);
            if(!$dt_periodo){
                throw new Exception("El periodo '{$periodo}' no es válido");
            }
            $data = $this->repo->getByNumCuentaAndPeriodo($numcuenta, $dt_periodo);
            
            $headers = [
                //"co_id" => ["label" => "CO_ID"],
                "dn_num" => ["label" => "DN_NUM"],
                "fup_seq" => ["label" => "FUP_SEQ"],
                "servicio" => ["label" => "SERVICIO"],
                "long_name" => ["label" => "LONG_NAME"],
                "inicio" => ["label" => "INICIO"],
                "fin" => ["label" => "FIN"],
                "des" => ["label" => "DES"],
                "u_libres" => ["label" => "U_LIBRES"],
            ];
            $options = [
                // 'y_start_index' => 0,
                // 'x_start_index' => 0,
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

            
            $this->exportService->loadData($headers, $data, $options);
            $content = $this->exportService->getWriter(WriterType::XLSX)->getOutput();

            //dd($data);
            
            //$this->exportService->reset();
            //$this->exportService->loadData($headers, $data, []);
            $this->reportLog($this->exportService, $dt_start, new DateTime());

            return new Response([], [
                "filename" => "GB_OTORGADOS_CAJA_AREQUIPA".$dt_periodo->format("Ym").".xlsx",
                "content" => $content
            ]);
        } catch (\Throwable $th) {
            $this->reportLog(null, $dt_start, new DateTime(), ['mensaje' => $th->getMessage()]);
            throw $th;
        }
    }

    private function reportLog(?ExportService $exportService, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        $filename = "GB_OTORGADOS_CAJA_AREQUIPA_".$ini->format('YmdHis');
        $data = array_merge([
            'name' => 'CAJA_AREQUIPA',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => "caja-arequipa.gb-otorgados",
        ], $extra_data);
        $this->saveReportLog->fromExport($exportService, $data, $filename, 'CAJA_AREQUIPA');
    }
}
