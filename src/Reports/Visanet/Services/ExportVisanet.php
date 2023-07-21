<?php

namespace AMovil\Reports\Visanet\Services;

use AMovil\Reports\Visanet\Domain\TipoReporte;
use AMovil\Reports\Visanet\Domain\VisanetRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;
use DateTime;

class ExportVisanet
{
    private $repo;
    private $exportService;
    public function __construct(VisanetRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke(?string $numCuenta, ?string $periodo, ?string $tipo_reporte)
    {
        $numCuenta = str_replace([" ", "\n", "\r", "\t"], ["", "", "", ""], $numCuenta);
        $arrayNumCuenta = explode(",", $numCuenta);

        $dt_periodo = DateTime::createFromFormat("Ym", $periodo);
        $tipo = TipoReporte::getById($tipo_reporte);

        $options = [
            "title" => $tipo->getLabel(),
            'styles' => [
                'header' => [
                    'font' => ['bold' => true, 'size' => 9],
                ],
                'body' => [
                    'font' => ['size' => 9],
                ]
            ]
        ];

        $numberFormat = [
            'numberFormat' => ['formatCode' => SpreadsheetStyle\NumberFormat::FORMAT_NUMBER_00],
        ];
        $textoFormat = ['numberFormat' => ['formatCode' => SpreadsheetStyle\NumberFormat::FORMAT_TEXT]];

        switch ($tipo_reporte) {
            case '1':
                $data = $this->repo->getFacturaDetalladaByNumCuenta_Periodo($arrayNumCuenta, $dt_periodo);

                $headers = [
                    "cuenta_cliente" => ["label" => "CUENTA_CLIENTE"],
                    "invoicenumber" => ["label" => "INVOICENUMBER", 'bodyStyles' => $textoFormat],
                    "msisdn" => ["label" => "MSISDN"],
                    "rpc" => ["label" => "RPC", 'bodyStyles' => $numberFormat],
                    "internet" => ["label" => "INTERNET", 'bodyStyles' => $numberFormat],
                    "black" => ["label" => "BLACK", 'bodyStyles' => $numberFormat],
                    "paquete_sms" => ["label" => "PAQUETE_SMS", 'bodyStyles' => $numberFormat],
                    "paquete_roaming" => ["label" => "PAQUETE_ROAMING", 'bodyStyles' => $numberFormat],
                    "trafico_datos" => ["label" => "TRAFICO_DATOS", 'bodyStyles' => $numberFormat],
                    "mensaje_texto" => ["label" => "MENSAJE_TEXTO", 'bodyStyles' => $numberFormat],
                    "otros" => ["label" => "OTROS", 'bodyStyles' => $numberFormat],
                    "trafico_local" => ["label" => "TRAFICO_LOCAL", 'bodyStyles' => $numberFormat],
                    "sub_total" => ["label" => "SUB_TOTAL", 'bodyStyles' => $numberFormat],           
                ];

                $this->exportService->loadData($headers, $data, $options);
                break;
            case '2':
                $data = $this->repo->getConsumoDatosByNumCuenta_Periodo($arrayNumCuenta, $dt_periodo);
                $headers = [
                    "nro_tel_origen" => ["label" => "NRO_TEL_ORIGEN"],
                    "clientacctno" => ["label" => "CLIENTACCTNO"],         
                    "smsdate" => ["label" => "SMSDATE"],
                    "consumo_bytes" => ["label" => "CONSUMO_BYTES", 'bodyStyles' => $numberFormat],         
                    "consumo_kb" => ["label" => "CONSUMO_KB", 'bodyStyles' => $numberFormat],         
                    "consumo_mb" => ["label" => "CONSUMO_MB", 'bodyStyles' => $numberFormat],         
                ];
                $this->exportService->loadData($headers, $data, $options);
                break;
            default:
                break;
        }
        
        $content = $this->exportService->getWriter(WriterType::XLSX)->getOutput();
        return new Response([], [
            "filename" => $tipo->getLabel()."_{$periodo}.xlsx",
            "type" => "xlsx",
            "content" => $content
        ]);
    }
}
