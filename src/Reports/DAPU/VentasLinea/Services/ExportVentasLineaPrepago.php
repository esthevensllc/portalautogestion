<?php

namespace AMovil\Reports\DAPU\VentasLinea\Services;

use AMovil\Reports\DAPU\VentasLinea\Domain\VentasLineaRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportVentasLineaPrepago
{
    private $repo;
    private $exportService;

    public function __construct(VentasLineaRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }
    
    public function __invoke($type, $dni, $fono)
    {
        $data = $this->repo->getPrepagoByDni_Fono_Periodo($dni, $fono);
        $headers = [
            "fecha_venta" => ["label" => "FECHA_VENTA"],
            "linea" => ["label" => "LINEA"],
            "tipo_doc_cliente" => ["label" => "TIPO_DOC_CLIENTE"],
            "documento_cliente" => ["label" => "DOCUMENTO_CLIENTE"],
            "nombres_cliente" => ["label" => "NOMBRES_CLIENTE"],
            "apellidos_cliente" => ["label" => "APELLIDOS_CLIENTE"],
            "pedido" => ["label" => "PEDIDO"],
            "imei" => ["label" => "IMEI"],
            "codigo_equipo" => ["label" => "CODIGO_EQUIPO"],
            "codigo_vendedor" => ["label" => "CODIGO_VENDEDOR"],
            "nombres_vendedor" => ["label" => "NOMBRES_VENDEDOR"],
            "apellido_paterno_vendedor" => ["label" => "APELLIDO_PATERNO_VENDEDOR"],
            "apellido_materno_vendedor" => ["label" => "APELLIDO_MATERNO_VENDEDOR"],
            "documento_vendedor" => ["label" => "DOCUMENTO_VENDEDOR"],
            "canal_venta" => ["label" => "CANAL_VENTA"],
            "desc_oficina_venta" => ["label" => "DESC_OFICINA_VENTA"],
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
            'filename' => "VENTAS_LINEA_PREPAGO_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }
}
