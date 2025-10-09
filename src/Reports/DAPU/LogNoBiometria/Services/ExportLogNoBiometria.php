<?php

namespace AMovil\Reports\DAPU\LogNoBiometria\Services;

use AMovil\Reports\DAPU\LogNoBiometria\Domain\LogNoBiometriaRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportLogNoBiometria
{
    private $repo;
    private $exportService;
    public function __construct(LogNoBiometriaRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($type, $dni, $periodo): Response
    {
        $dtPeriodo = DateTime::createFromFormat("Y-m-d H", $periodo);
        $data = $this->repo->getByDniAndPeriodo($dni, $dtPeriodo);
        $headers = [
            "biom_tipovalidacion" => ["label" => "BIOM_TIPOVALIDACION"],
            "biom_idpadre" => ["label" => "BIOM_IDPADRE"],
            "biom_rptaval" => ["label" => "BIOM_RPTAVAL"],
            "biom_aplicacion" => ["label" => "BIOM_APLICACION"],
            "biom_nrodocumento" => ["label" => "BIOM_NRODOCUMENTO"],
            "biom_observacion" => ["label" => "BIOM_OBSERVACION"],
            "biom_fecha_crea" => ["label" => "BIOM_FECHA_CREA"],
            "biom_codigo" => ["label" => "BIOM_CODIGO"],
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
            'filename' => "LOG_BIOMETRIA_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }
}
