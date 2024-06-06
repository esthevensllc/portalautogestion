<?php

namespace AMovil\Reports\DAPU\ListaEir\Services;

use AMovil\Reports\DAPU\ListaEir\Domain\ListaEirRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportListaEir
{
    private $repo;
    private $exportService;

    public function __construct(ListaEirRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($type, $imei)
    {
        $data = $this->repo->getByImei($imei);
        $headers = [
            "transact_date" => ["label" => "FECHA"],
            "imei" => ["label" => "IMEI"],
            "fecha_ingreso_blo" => ["label" => "FECHA_INGRESO_BLO"],
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
            'filename' => "LISTA_EIR_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }
}
