<?php

namespace AMovil\Reports\DAPU\ConsultaImei\Services;

use AMovil\Reports\DAPU\ConsultaImei\Domain\ConsultaImeiRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportConsultaImei
{
    private $finder;
    private $exportService;

    public function __construct(ImeiFinder $finder, ExportService $exportService)
    {
        $this->finder = $finder;
        $this->exportService = $exportService;
    }

    public function __invoke($msisnd, $fecha, $exportType)
    {
        $response = $this->finder->__invoke($msisnd, $fecha);

        $headers = [
            "msisdn" => ["label" => "MSISDN"],
            "nombres" => ["label" => "NOMBRES"],
            "ape_paterno" => ["label" => "APE_PATERNO"],
            "ape_materno" => ["label" => "APE_MATERNO"],
            "nro_documento" => ["label" => "NRO_DOCUMENTO"],
            "razon_social" => ["label" => "RAZON_SOCIAL"],
            "imsi" => ["label" => "IMSI"],
            "imei" => ["label" => "IMEI"],
            "fecha_actualizacion" => ["label" => "FECHA_ACTUALIZACION"],
        ];

        $options = [
            'styles' => [
                'header' => ['font' => ['bold' => true]]
            ]
        ];

        $this->exportService->loadData($headers, $response->data(), $options);
        $content = $this->exportService->getWriter($exportType)->getOutput();
        $dt = new DateTime();
        
        return new Response([], [
            'filename' => "EQUIPO_BIOMETRIA_".$dt->format("Ymd").".".strtolower($exportType),
            'type' => strtolower($exportType),
            'content' => $content
        ]);
    }
}
