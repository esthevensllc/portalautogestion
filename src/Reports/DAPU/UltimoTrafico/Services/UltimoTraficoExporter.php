<?php

namespace AMovil\Reports\DAPU\UltimoTrafico\Services;

use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class UltimoTraficoExporter
{
    private $finderService;
    private $exportService;

    public function __construct(UltimoTraficoFinder $finderService, ExportService $exportService)
    {
        $this->finderService = $finderService;
        $this->exportService = $exportService;
    }

    public function __invoke(int $tipoInput, array $values, $exportType): Response
    {
        $data = $this->finderService->__invoke($tipoInput, $values)->data();
        return $this->export($data, $exportType);
    }

    public function fromFile($tipoInput, FileInput $file, $exportType){
        $data = $this->finderService->fromFile($tipoInput, $file)->data();
        return $this->export($data, $exportType);
    }

    private function export($data, $exportType): Response {
        $headers = [
            "msisdn" => ["label" => "MSISDN"],
            "imsi" => ["label" => "IMSI"],
            "imei" => ["label" => "IMEI"],
            "fec_ultimo_trafico" => ["label" => "FEC_ULTIMO_TRAFICO"],
        ];

        $options = [
            'styles' => [
                'header' => ['font' => ['bold' => true]]
            ]
        ];

        $this->exportService->loadData($headers, $data, $options);
        $content = $this->exportService->getWriter($exportType)->getOutput();
        $dt = new DateTime();
        
        return new Response([], [
            'filename' => "ULT_TRAFICO_".$dt->format("Ymd").".".strtolower($exportType),
            'type' => strtolower($exportType),
            'content' => $content
        ]);
    }
}
