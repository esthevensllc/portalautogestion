<?php

namespace AMovil\Reports\DAPU\FONO\Services;

use AMovil\Reports\DAPU\FONO\Domain\FonoRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportDataDni
{
    private $repo;
    private $exportService;
    public function __construct(FonoRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($fono, $type): Response
    {
        $data = $this->repo->getByFono($fono);
        $headers = [
            "fono" => ["label" => "FONO"],
            "nombre" => ["label" => "NOMBRE"],
            "fecha_alta" => ["label" => "FECHA_ALTA"],
            "fecha_baja" => ["label" => "FECHA_BAJA"],
            "numero_doc" => ["label" => "NUMERO_DOC"],
            "tipo_doc" => ["label" => "TIPO_DOC"],
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
            'filename' => "DNI_FONO_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }
}
