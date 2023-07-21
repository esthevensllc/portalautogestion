<?php

namespace AMovil\Reports\DAPU\Lineas\Services;

use AMovil\Reports\DAPU\Lineas\Domain\LineaRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportDataLinea
{
    private $repo;
    private $exportService;

    public function __construct(LineaRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($linea, $type): Response
    {
        $data = $this->repo->getUsuariosByLinea($linea);

        $headers = [
            "linea" => ["label" => "LINEA"],
            "cliente" => ["label" => "CLIENTE"],
            "tipo_doc" => ["label" => "TIPO_DOC"],
            "num_doc" => ["label" => "NUM_DOC"],
            "direccion" => ["label" => "DIRECCIÓN"],
            "departamento" => ["label" => "DEPARTAMENTO"],
            "provincia" => ["label" => "PROVINCIA"],
            "distrito" => ["label" => "DISTRITO"],
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
            'filename' => "CONSULTA_LINEA_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }
}
