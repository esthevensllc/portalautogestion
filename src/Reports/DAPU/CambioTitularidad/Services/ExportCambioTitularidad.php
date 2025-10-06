<?php

namespace AMovil\Reports\DAPU\CambioTitularidad\Services;

use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportCambioTitularidad
{
    private $finderService;
    private $exportService;

    public function __construct(CambioTitularidadFinder $finderService, ExportService $exportService)
    {
        $this->finderService = $finderService;
        $this->exportService = $exportService;
    }

    public function __invoke(string $linea, $exportType): Response
    {
        $data = $this->finderService->__invoke($linea)->data();
        return $this->export($data, $exportType);
    }

    private function export($data, $exportType): Response {
        $headers = [
            "codigo_subclase" => ["label" => "CODIGO_SUBCLASE"],
            "tipo_linea" => ["label" => "TIPO_LINEA"],
            "desc_tipificacion" => ["label" => "DESC_TIPIFICACION"],
            "fecha_transaccion" => ["label" => "FECHA_TRANSACCION"],
            "numero_telefono" => ["label" => "NUMERO_TELEFONO"],
            "nombre_cliente_cedente" => ["label" => "NOMBRE_CLIENTE_CEDENTE"],
            "numero_doc_cedente" => ["label" => "NUMERO_DOC_CEDENTE"],
            "tipo_doc_cedente" => ["label" => "TIPO_DOC_CEDENTE"],
            "nombre_cliente_receptor" => ["label" => "NOMBRE_CLIENTE_RECEPTOR"],
            "tipo_doc_receptor" => ["label" => "TIPO_DOC_RECEPTOR"],
            "numero_doc_receptor" => ["label" => "NUMERO_DOC_RECEPTOR"],
            "codigo_tipificacion" => ["label" => "CODIGO_TIPIFICACION"],
            "usuario_reg" => ["label" => "USUARIO_REG"],
            "punto_atencion" => ["label" => "PUNTO_ATENCION"],
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
            'filename' => "CAMBIO_TITULARIDAD_".$dt->format("Ymd").".".strtolower($exportType),
            'type' => strtolower($exportType),
            'content' => $content
        ]);
    }
}
