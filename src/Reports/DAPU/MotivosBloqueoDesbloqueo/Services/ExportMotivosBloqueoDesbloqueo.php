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

    public function __invoke(int $tipoInput, $type, $value)
    {
        $data = [];
        if ($tipoInput === 1) {
            $data = $this->repo->getByLinea($value);
        } else if ($tipoInput === 2) {
            $data = $this->repo->getByImei($value);
        }
        $headers = [
            "id_termovimiento" => ["label" => "ID_TERMOVIMIENTO"],
            "imei" => ["label" => "IMEI"],
            "linea" => ["label" => "LINEA"],
            "cod_asesor" => ["label" => "COD_ASESOR"],
            "fecha_transaccion" => ["label" => "FECHA_TRANSACCION"],
            "estado_transaccion" => ["label" => "ESTADO_TRANSACCION"],
            "motivo_transaccion" => ["label" => "MOTIVO_TRANSACCION"],
            "detalle_equipo" => ["label" => "DETALLE_EQUIPO"],
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
