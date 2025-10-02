<?php

namespace AMovil\Reports\DAPU\Lineas\Services;

use AMovil\Reports\DAPU\Lineas\Domain\LineaRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExportDataLinea
{
    private $repo;
    private $finderService;
    private $exportService;

    public function __construct(LineaRepository $repo, GetDataLinea $finderService, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->finderService = $finderService;
        $this->exportService = $exportService;
    }

    public function __invoke(int $tipoInput, array $values, $exportType): Response
    {
        $data = $this->finderService->__invoke($tipoInput, $values)->data();
        return $this->export($data, $exportType);
    }

    public function fromFile($tipoInput, FileInput $file, $exportType){
        $data = $this->finderService->fromFile($tipoInput, $file)->data();
        return $this->export($data, $exportType);
    }

    private function export($data, $exportType): Response {
        $headers = [
            "linea" => ["label" => "LINEA"],
            "producto" => ["label" => "PRODUCTO"],
            "tipo_doc" => ["label" => "TIPO_DOC"],
            "nro_doc" => ["label" => "NRO_DOC"],
            "nombre" => ["label" => "NOMBRE"],
            "mail" => ["label" => "MAIL"],
            "modalidad" => ["label" => "MODALIDAD"],
            "tipo" => ["label" => "TIPO"],
            "estado" => ["label" => "ESTADO"],
            "motivo_estado" => ["label" => "MOTIVO_ESTADO"],
            "f_inicio" => ["label" => "F_INICIO"],
            "f_fin" => ["label" => "F_FIN"],
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
        $content = $this->exportService->getWriter($exportType)->getOutput();
        $dt = new DateTime();
        
        return new Response([], [
            'filename' => "CONSULTA_LINEA_".$dt->format("Ymd").".".strtolower($exportType),
            'type' => strtolower($exportType),
            'content' => $content
        ]);
    }
}
