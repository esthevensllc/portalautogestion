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
            "fecha" => ["label" => "FECHA"],
            "accion" => ["label" => "ACCION"],
            "modalidad" => ["label" => "MODALIDAD"],
            "linea" => ["label" => "LINEA"],
            "histn_imsi" => ["label" => "HISTN_IMSI"],
            "histv_imei" => ["label" => "HISTV_IMEI"],
            "tipo_documento" => ["label" => "TIPO_DOCUMENTO"],
            "nro_documento" => ["label" => "NRO_DOCUMENTO"],
            "nombre" => ["label" => "NOMBRE"],
            "apellidos" => ["label" => "APELLIDOS"],
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
