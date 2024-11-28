<?php

namespace AMovil\Reports\DAPU\HistoricoBloqueos\Services;

use AMovil\Reports\DAPU\HistoricoBloqueos\Domain\HistoricoBloqueoRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportHistoricoBloqueo
{
    private $finder;
    private $exportService;
    public function __construct(GetHistoricoBloqueo $finder, ExportService $exportService)
    {
        $this->finder = $finder;
        $this->exportService = $exportService;
    }

    public function __invoke($exportType, $imeis): Response
    {
        $data = $this->finder->__invoke($imeis)->data();
        return $this->export($exportType, $data);       
    }

    public function fromFile($exportType, FileInput $file){
        $data = $this->finder->fromFile($file)->data();
        return $this->export($exportType, $data);
    }

    public function export($type, $data): Response
    {
        $headers = [
            "histd_fecha_mensaje" => ["label" => "HISTD_FECHA_MENSAJE"],
            "imei" => ["label" => "IMEI"],
            "histn_num_servici" => ["label" => "HISTN_NUM_SERVICI"],
            "histv_imei" => ["label" => "HISTV_IMEI"],
            "histd_fecha_reporte" => ["label" => "HISTD_FECHA_REPORTE"],
            "nombre_apellidos" => ["label" => "NOMBRE_APELLIDOS"],
            "histn_tipo_documento" => ["label" => "HISTN_TIPO_DOCUMENTO"],
            "histv_numero_documento" => ["label" => "HISTV_NUMERO_DOCUMENTO"],
            "histv_tipo_solicitud" => ["label" => "HISTV_TIPO_SOLICITUD"],
            "histv_estado" => ["label" => "HISTV_ESTADO"],
            "histv_accion_realizar" => ["label" => "HISTV_ACCION_REALIZAR"],
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
            'filename' => "HISTORICO_BLOQUEOS_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }
}
