<?php

namespace AMovil\Reports\DAPU\MotivosBloqueoDesbloqueo\Services;

use AMovil\Reports\DAPU\MotivosBloqueoDesbloqueo\Domain\MotivosBloqueoDesbloqueoRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportMotivosBloqueoDesbloqueo
{
    private $repo;
    private $exportService;

    public function __construct(MotivosBloqueoDesbloqueoRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($type, $imei)
    {
        $data = $this->repo->getByImei($imei);
        $headers = [
            "id_termovimiento" => ["label" => "ID_TERMOVIMIENTO"],
            "numero_imei" => ["label" => "IMEI"],
            "ter_numerolinea" => ["label" => "TER_NUMEROLINEA"],
            "ter_reportante" => ["label" => "TER_REPORTANTE"],
            "ter_asesor_servicio" => ["label" => "TER_ASESOR_SERVICIO"],
            "ter_fecregistro" => ["label" => "TER_FECREGISTRO"],
            "ter_estado" => ["label" => "TER_ESTADO"],
            "ter_des_motivo" => ["label" => "TER_DES_MOTIVO"],
            "ter_marca_modelo" => ["label" => "TER_MARCA_MODELO"],
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
            'filename' => "MOTIVOS_BLOQUEO_DESBLOQUEO_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }
}
