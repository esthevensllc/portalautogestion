<?php

namespace AMovil\Reports\DAPU\VentasLinea\Services;

use AMovil\Reports\DAPU\VentasLinea\Domain\VentasLineaRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportVentasLinea
{
    private $repo;
    private $exportService;

    public function __construct(VentasLineaRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($type, $dni, $fono, $periodo)
    {
        $data = $this->repo->getByDni_Fono_Periodo($dni, $fono, $periodo);
        $headers = [
            "fecha_venta" => ["label" => "FECHA_VENTA"],
            "imei" => ["label" => "IMEI"],
            "n_doc" => ["label" => "N_DOC"],
            "cliente" => ["label" => "CLIENTE"],
            "fono" => ["label" => "FONO"],
            "producto" => ["label" => "PRODUCTO"],
            "plan_producto" => ["label" => "PLAN_PRODUCTO"],
            "sales_person_name" => ["label" => "SALES_PERSON_NAME"],
            "pdv_desc" => ["label" => "PDV_DESC"],
            "pdv_razon_social_desc" => ["label" => "PDV_RAZON_SOCIAL_DESC"],
            "pdv_channel_desc" => ["label" => "PDV_CHANNEL_DESC"],
            "canal" => ["label" => "CANAL"],
            "sales_reason_desc" => ["label" => "SALES_REASON_DESC"],
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
            'filename' => "VENTAS_LINEA_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }
}
