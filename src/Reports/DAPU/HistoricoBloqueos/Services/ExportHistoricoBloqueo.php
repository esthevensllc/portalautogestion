<?php

namespace AMovil\Reports\DAPU\HistoricoBloqueos\Services;

use AMovil\Reports\DAPU\HistoricoBloqueos\Domain\HistoricoBloqueoRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportHistoricoBloqueo
{
    private $repo;
    private $exportService;
    public function __construct(HistoricoBloqueoRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($type, $imei): Response
    {
        $data = $this->repo->getByImei($imei);
        $headers = [
            "imei" => ["label" => "IMEI"],
            "fecha_registro" => ["label" => "FECHA_REGISTRO"],
            "linea" => ["label" => "LINEA"],
            "ter_estado" => ["label" => "TER_ESTADO"],
            "ter_des_motivo" => ["label" => "TER_DES_MOTIVO"],
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
