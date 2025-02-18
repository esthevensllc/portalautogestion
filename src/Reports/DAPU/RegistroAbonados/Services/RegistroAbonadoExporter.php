<?php

namespace AMovil\Reports\DAPU\RegistroAbonados\Services;

use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class RegistroAbonadoExporter
{
    private $finderService;
    private $exportService;

    public function __construct(RegistroAbonadoFinder $finderService, ExportService $exportService)
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
            "nombres" => ["label" => "NOMBRES"],
            "ape_paterno" => ["label" => "APE_PATERNO"],
            "ape_materno" => ["label" => "APE_MATERNO"],
            "razon_social" => ["label" => "RAZON_SOCIAL"],
            "tipo_documento" => ["label" => "TIPO_DOCUMENTO"],
            "nro_documento" => ["label" => "NRO_DOCUMENTO"],
            "imsi" => ["label" => "IMSI"],
            "fch_activacion" => ["label" => "FCH_ACTIVACION"],
            "estado_servicio" => ["label" => "ESTADO_SERVICIO"],
            "motivo_suspension" => ["label" => "MOTIVO_SUSPENSION"],
            "motivo_baja" => ["label" => "MOTIVO_BAJA"],
            "vinculacion_servicio" => ["label" => "VINCULACION_SERVICIO"],
            "imei" => ["label" => "IMEI"],
            "origen_equipo" => ["label" => "ORIGEN_EQUIPO"],
            "fecha_actualizacion" => ["label" => "FECHA_ACTUALIZACION"],
            "fecha_reporte" => ["label" => "FECHA_REPORTE"],
            "msisdn_anterior" => ["label" => "MSISDN_ANTERIOR"],
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
            'filename' => "REGISTRO_ABONADOS_".$dt->format("Ymd").".".strtolower($exportType),
            'type' => strtolower($exportType),
            'content' => $content
        ]);
    }
}
