<?php

namespace AMovil\Reports\DAPU\ServicioMovil\Services;

use AMovil\Reports\DAPU\ServicioMovil\Domain\ServicioMovilRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;
use Exception;

class ExportServicioMovil
{
    private $repo;
    private $exportService;

    public function __construct(ServicioMovilRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($type, $msisdn, $fecha)
    {
        $dtFecha = DateTime::createFromFormat("Y-m-d", $fecha);
        if($dtFecha === false){
            throw new Exception("La fecha no es valida");
        }
        $data = $this->repo->getByMsisdnFecha($msisdn, $dtFecha);
        $headers = [
            "create_date" => ["label" => "CREATE_DATE"],
            "reason_3" => ["label" => "REASON_3"],
            "title" => ["label" => "TITLE"],
            "last_name" => ["label" => "LAST_NAME"],
            "first_name" => ["label" => "FIRST_NAME"],
            "x_doc_type" => ["label" => "X_DOC_TYPE"],
            "x_doc_num" => ["label" => "X_DOC_NUM"],
            "phone" => ["label" => "PHONE"],
            "lugar" => ["label" => "LUGAR"],
            "x_subclase_code" => ["label" => "X_SUBCLASE_CODE"],
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
            'filename' => "SERVICIO_MOVIL_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }
}
