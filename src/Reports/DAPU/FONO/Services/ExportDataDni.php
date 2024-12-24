<?php

namespace AMovil\Reports\DAPU\FONO\Services;

use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportDataDni
{
    private $finder;
    private $exportService;
    public function __construct(GetDataDni $finder, ExportService $exportService)
    {
        $this->finder = $finder;
        $this->exportService = $exportService;
    }

    public function __invoke($lineas, $exportType)
    {
        $data = $this->finder->__invoke($lineas)->data();
        return $this->export($data, $exportType);
    }

    public function fromFile(FileInput $file, $exportType)
    {
        $data = $this->finder->fromFile($file)->data();
        return $this->export($data, $exportType);
    }

    public function export($data, $type): Response
    {
        $headers = [
            "msisdn" => ["label" => "MSISDN"],
            "imsi" => ["label" => "IMSI"],
            "tecnologia_red_chip" => ["label" => "TECNOLOGIA_RED_CHIP"],
            "tplname" => ["label" => "TPLNAME"],
            "provi_volte" => ["label" => "PROVI_VOLTE"],
            "simcard_3g_sinvolte" => ["label" => "SIMCARD_3G_SINVOLTE"],
            "simcard_3g_convolte" => ["label" => "SIMCARD_3G_CONVOLTE"],
        ];

        $options = [
            'styles' => [
                'header' => ['font' => ['bold' => true]]
            ]
        ];

        $this->exportService->loadData($headers, $data, $options);
        $content = $this->exportService->getWriter($type)->getOutput();
        $dt = new DateTime();
        
        return new Response([], [
            'filename' => "DNI_FONO_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }
}
