<?php

namespace AMovil\Reports\General\FacturacionAdelantada\Services;

use AMovil\Reports\General\FacturacionAdelantada\Domain\FacturacionAdelantadaRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportFacturacionAdelantada
{
    private $repo;
    private $exportService;

    public function __construct(FacturacionAdelantadaRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($tipoInput, $periodo, $fechaIni, $fechaFin, $cuenta, $numeroFactura)
    {
        $dtFechaIni = null;
        $dtFechaFin = null;
        $data = [];

        if ($tipoInput === "1") {
            $dtPeriodo = DateTime::createFromFormat("Ym", $periodo);
            if($dtPeriodo === false){
                throw new Exception("El periodo '{$periodo}' es invalido");
            }
            $data = $this->repo->getReporteByPeriodo($cuenta, $numeroFactura, $dtPeriodo);
        }else{
            $dtFechaIni = DateTime::createFromFormat("Y-m-d", $fechaIni);
            $dtFechaFin = DateTime::createFromFormat("Y-m-d", $fechaFin);
            $data = $this->repo->getReporteByDates($cuenta, $numeroFactura, $dtFechaIni, $dtFechaFin);
        }

        $options = [
            "sheetIndex" => 0,
            'title' => "FACTURACION_ADELANTADA",
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
            "cuenta" => ["label" => "CUENTA"],
            "ciclo" => ["label" => "CICLO"],
            "nro_factura" => ["label" => "NRO_FACTURA"],
            "nro_telef_orig" => ["label" => "NRO_TELEF_ORIG"],
            "fecha" => ["label" => "FECHA"],
            "hora_inicio" => ["label" => "HORA_INICIO"],
            "hora_fin" => ["label" => "HORA_FIN"],
            "pais" => ["label" => "PAIS"],
            "telef_destino" => ["label" => "TELEF_DESTINO"],
            "consumo" => ["label" => "CONSUMO"],
            "tipo_servicio" => ["label" => "TIPO_SERVICIO"],
            "destino" => ["label" => "DESTINO"],
            "operador" => ["label" => "OPERADOR"],
            "tipo_llamada" => ["label" => "TIPO_LLAMADA"],
            "cargo_final" => ["label" => "CARGO_FINAL"],
        ];

        $this->exportService->loadData($headers, $data, $options);
        $exportContent = $this->exportService->getWriter(WriterType::XLSX)->getOutput();
        
        return new Response([], [
            "filename" => "FACTURACION_ADELANTADA.xlsx",
            "type" => "xlsx",
            "content" => $exportContent,
        ]);
    }
}
