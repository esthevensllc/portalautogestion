<?php

namespace AMovil\Reports\DAPU\SuspensionServicio\Services;

use AMovil\Reports\DAPU\SuspensionServicio\Domain\SuspensionServicioRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;

class ExportSuspensionServicio
{
    private $repo;
    private $exportService;
    public function __construct(SuspensionServicioRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($msisdn, $dni, $fecha1, $fecha2, $type)
    {
        WriterType::guard($type);

        $headers = [
            "codigo_cliente" => ["label" => "Codigo cliente"],
            "cliente" => ["label" => "Cliente"],
            "nro_doc" => ["label" => "Nro doc"],
            "doc" => ["label" => "Doc"],
            "msisdn" => ["label" => "Número"],
            "estado" => ["label" => "Estado"],
            "fecha_suspension" => ["label" => "Fecha suspensión"],
            "fecha_alta" => ["label" => "Fecha alta"],
            "fecha_baja" => ["label" => "Fecha baja"],
            "segmento" => ["label" => "Segmento"],
            "motivo_estado" => ["label" => "Motivo estado"]
        ];
        
        $dt1 = DateTime::createFromFormat("Y-m-d", $fecha1);
        $dt2 = DateTime::createFromFormat("Y-m-d", $fecha2);
        $data = $this->repo->getByMsisdn($msisdn, $dni, $dt1, $dt2);

        $this->exportService->loadData($headers, $data);
        $content = $this->exportService->getWriter($type)->getOutput();
        $dt = new DateTime();
        
        return new Response([], [
            'filename' => "SUSPENSION_SERVICIO_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }
}
