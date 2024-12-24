<?php

namespace AMovil\Reports\DAPU\Sots\Services;

use AMovil\Reports\DAPU\Sots\Domain\DapuSotRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class DapuSotExporter
{
    private $repo;
    private $exportService;

    public function __construct(DapuSotRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($docCliente, $exportType)
    {
        $data = $this->repo->getByDocCliente($docCliente);
        
        $headers = [
            "contd_fecha_contrato" => ["label" => "CONTD_FECHA_CONTRATO"],
            "dn_num" => ["label" => "DN_NUM"],
            "contn_numero_contrato" => ["label" => "CONTN_NUMERO_CONTRATO"],
            "contn_numero_sec" => ["label" => "CONTN_NUMERO_SEC"],
            "paquete" => ["label" => "PAQUETE"],
            "contc_oficina_venta" => ["label" => "CONTC_OFICINA_VENTA"],
            "pdv_canal" => ["label" => "PDV_CANAL"],
            "pdv_des" => ["label" => "PDV_DES"],
            "pdv_razon_social" => ["label" => "PDV_RAZON_SOCIAL"],
            "tipo_doc_cliente" => ["label" => "TIPO_DOC_CLIENTE"],
            "contv_nro_doc_cliente" => ["label" => "CONTV_NRO_DOC_CLIENTE"],
            "nombre_razon_social_cliente" => ["label" => "NOMBRE_RAZON_SOCIAL_CLIENTE"],
            "contv_codigo_vendedor" => ["label" => "CONTV_CODIGO_VENDEDOR"],
            "contv_vendedor" => ["label" => "CONTV_VENDEDOR"],
            "nro_sot" => ["label" => "NRO_SOT"],
            "fecultest" => ["label" => "FECULTEST"],
            "fecha_activacion" => ["label" => "FECHA_ACTIVACION"],
            "direccion" => ["label" => "DIRECCION"],
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
            'filename' => "EQUIPO_BIOMETRIA_".$dt->format("Ymd").".".strtolower($exportType),
            'type' => strtolower($exportType),
            'content' => $content
        ]);
    }
}
