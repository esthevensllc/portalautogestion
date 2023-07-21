<?php

namespace AMovil\Reports\DetalleTasado\Services;

use AMovil\Reports\DetalleTasado\Domain\DetalleTasadoRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportDetalleTasado
{
    private $repo;
    private $exportService;

    public function __construct(DetalleTasadoRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($numerosCuenta, $tipoInput, $fecha, $fechaIni, $fechaFin): Response
    {
        $dtFechaIni = null;
        $dtFechaFin = null;
        if($tipoInput === "1"){
            $dtFechaIni = DateTime::createFromFormat("Y-m-d", $fecha);
            $dtFechaFin = DateTime::createFromFormat("Y-m-d", $fecha);
        }else{
            $dtFechaIni = DateTime::createFromFormat("Y-m-d", $fechaIni);
            $dtFechaFin = DateTime::createFromFormat("Y-m-d", $fechaFin);
        }

        $data = $this->repo->getReporte(explode(",", str_replace(" ", "", $numerosCuenta)), $dtFechaIni, $dtFechaFin);

        $options = [
            "sheetIndex" => 0,
            'title' => "DETALLE TASADO",
            'styles' => [
                'header' => [
                    'font' => ['bold' => true, 'size' => 9],
                    'borders'=> [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => array('rgb'=>'000000')
                        ]
                    ]
                ],
                'body' => [
                    'font' => ['size' => 9]
                ]
            ]
        ];

        $headers = [
            "fch_trafico" => ["label" => "FCH_TRAFICO"],
            "msisdn" => ["label" => "MSISDN"],
            "plan" => ["label" => "PLAN"],
            "servicio_des" => ["label" => "SERVICIO_DES"],
            "trafico_bytes" => ["label" => "TRAFICO_BYTES"],
        ];

        $this->exportService->loadData($headers, $data, $options);
        $exportContent = $this->exportService->getWriter(WriterType::XLSX)->getOutput();

        return new Response([], [
            "filename" => "DETALLE_TASADO.xlsx",
            "type" => "xlsx",
            "content" => $exportContent,
        ]);
    }
}
