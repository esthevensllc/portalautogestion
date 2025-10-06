<?php

namespace AMovil\Reports\DAPU\BusquedaSimCard\Services;

use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class BusquedaSimCardExporter
{
    private $finderService;
    private $exportService;

    public function __construct(BusquedaSimCardFinder $finderService, ExportService $exportService)
    {
        $this->finderService = $finderService;
        $this->exportService = $exportService;
    }

    public function __invoke(string $iccid, $exportType): Response
    {
        $data = $this->finderService->__invoke($iccid)->data();
        return $this->export($data, $exportType);
    }

    private function export($data, $exportType): Response {
        $headers = [
            "fecha_venta" => ["label" => "FECHA_VENTA"],
            "imei" => ["label" => "IMEI"],
            "codigo_chip" => ["label" => "CODIGO_CHIP"],
            "codigo_equipo" => ["label" => "CODIGO_EQUIPO"],
            "descripcion_equipo" => ["label" => "DESCRIPCION_EQUIPO"],
            "equipo" => ["label" => "EQUIPO"],
            "linea" => ["label" => "LINEA"],
            "modalidad" => ["label" => "MODALIDAD"],
            "canal" => ["label" => "CANAL"],
            "desc_oficina_venta" => ["label" => "DESC_OFICINA_VENTA"],
            "tipo" => ["label" => "TIPO"],
            "tipo_doc_cliente" => ["label" => "TIPO_DOC_CLIENTE"],
            "documento_cliente" => ["label" => "DOCUMENTO_CLIENTE"],
            "transaccion" => ["label" => "TRANSACCION"],
            "nombres_cliente" => ["label" => "NOMBRES_CLIENTE"],
            "apellidos_cliente" => ["label" => "APELLIDOS_CLIENTE"],
            "cod_vendedor" => ["label" => "COD_VENDEDOR"],
            "nombre_vendedor" => ["label" => "NOMBRE_VENDEDOR"],
            "nro_contrato" => ["label" => "NRO_CONTRATO"],
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
            'filename' => "BUSQUEDA_SIM_CARD_".$dt->format("Ymd").".".strtolower($exportType),
            'type' => strtolower($exportType),
            'content' => $content
        ]);
    }
}
