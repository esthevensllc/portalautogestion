<?php

namespace AMovil\Reports\BasesClientesPrepago\Services;

use AMovil\Reports\BasesClientesPrepago\Domain\BasesClientesPrepagoRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class BasesClientesPrepagoService
{
    private $repo;
    private $exportService;

    public function __construct(BasesClientesPrepagoRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function getDep()
    {
        $data = $this->repo->getDep();

        return $data;
    }

    public function getProv($dep)
    {
        $data = $this->repo->getProv($dep);

        return $data;
    }

    public function getDist($dep,$prov)
    {
        $data = $this->repo->getDist($dep,$prov);

        return $data;
    }

    public function __invoke($granu, $dep, $prov, $dist): Response
    {
        $data = $this->repo->getReporte($granu, $dep, $prov, $dist);

        $fecha = Carbon::now()->format('Ymd');

        switch ($granu) {
            case '0':
                // Code block to be executed if $status is 'active'
                $name_file = 'DEPARTAMENTO_'.utf8_decode($dep).'_'.$fecha;
                break;
            case '1':
                // Code block to be executed if $status is 'inactive'
                $name_file = 'PROVINCIA_'.utf8_decode($prov).'_'.$fecha;
                break;
            case '2':
                // Code block to be executed if $status is 'inactive'
                $name_file = 'DISTRITO_'.utf8_decode($dist).'_'.$fecha;
                break;
            default:
                // Code block to be executed if $status doesn't match any of the cases
                $name_file = $fecha;
                break;
        }

        $options = [
            "rowType" => "Array",
            "sheetIndex" => 0,
            'title' => "BASES CLIENTES PREPAGO",
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
            "fono" => ["label" => "FONO"],
        ];

        $this->exportService->loadData($headers, $data, $options);
        $exportContent = $this->exportService->getWriter(WriterType::XLSX)->getOutput();

        return new Response([], [
            "filename" => "BASE_CLIENTES_PREPAGO_".$name_file.".xlsx",
            "type" => "xlsx",
            "content" => $exportContent,
        ]);
    }
}