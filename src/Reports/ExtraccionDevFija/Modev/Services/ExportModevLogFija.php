<?php

namespace AMovil\Reports\ExtraccionDevFija\Modev\Services;

use AMovil\Reports\ExtraccionDevFija\Modev\Domain\ModevFijaRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFallasRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFijaTipoReporte;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportModevLogFija
{
    private $repo;
    private $informeFallarepo;
    private $exportService;

    public function __construct(ModevFijaRepository $repo, InformeFallasRepository $informeFallarepo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->informeFallarepo = $informeFallarepo;
        $this->exportService = $exportService;
    }

    public function __invoke($ticket)
    {
        /*$result = $this->informeFallarepo->getByCriteria(["ticket.eq.{$ticket}"]);
        if(count($result["data"]) === 0){
            throw new \Exception("No se encontro el informe de fallas para el ticket");
        }
        $tipo_reporte = (int) $result["data"][0]->tipo_reporte;
        $data = [];
        if ($tipo_reporte === InformeFijaTipoReporte::BY_CODCLI) {
            $data = $this->repo->getReporteModevMantenimiento([$ticket]);
        } else {
            $data = $this->repo->getReporteModev([$ticket]);
        }*/

        $tickets = explode(",", str_replace(" ", "", $ticket));
        $data = $this->repo->getReporteModev($tickets);

        if(count($data) === 0){
            return Response::respError(["message" => "El ticket no tiene ningun acreditado prepago"]);
        }

        $numberFormat = ['numberFormat' => ['formatCode' => SpreadsheetStyle\NumberFormat::FORMAT_TEXT]];

        $headers = [
            "ticket" => ["label" => "TICKET", 'bodyStyles' => $numberFormat],
            "tipo_documento" => ["label" => "TIPO_DOCUMENTO"],
            "nro_doc" => ["label" => "NRO_DOC"],
            "id_cliente" => ["label" => "ID_CLIENTE"],
            "msisdn" => ["label" => "MSISDN"],
            "servicio_afectado" => ["label" => "SERVICIO_AFECTADO"],
            "modo_contratacion" => ["label" => "MODO_CONTRATACION"],
            "cr_netocigv" => ["label" => "CR_NETOCIGV"],
            "minutos" => ["label" => "MINUTOS"],
            "mto_dev_facturacion" => ["label" => "MTO_DEV_FACTURACION"],
            "moneda_devol" => ["label" => "MONEDA_DEVOL"],
            "fecha_devolucion" => ["label" => "FECHA_DEVOLUCION"],
            // "factura_aplicada" => ["label" => "FACTURA_APLICADA"],
            "factura_aplicada" => ["label" => "FACTURA_APLICADA"],
            "estado" => ["label" => "ESTADO"],
            "fecha_baja" => ["label" => "FECHA_BAJA"],
            "nomcli" => ["label" => "NOMCLI"],
            "lugar_donde_cobrar" => ["label" => "LUGAR_DONDE_COBRAR"],
            "requisitos_para_el_cobro" => ["label" => "REQUISITOS_PARA_EL_COBRO"],
            "comunicacion" => ["label" => "COMUNICACION"],
            "medio_de_comunicacion" => ["label" => "MEDIO_DE_COMUNICACION"],
            "motivo_solo_cuando_no_corresponde" => ["label" => "MOTIVO_SOLO_CUANDO_NO_CORRESPONDE"],
            "comentarios" => ["label" => "COMENTARIOS"],
            "liberado" => ["label" => "LIBERADO"],
        ];

        $options = [
            'sheetIndex' => 0,
            // 'rowType' => 'object',
            'title' => "REP",
            'styles' => [
                'header' => [
                    'font' => ['bold' => true, 'size' => 9],
                    'borders'=> [
                        'allBorders' => ['borderStyle' => SpreadsheetStyle\Border::BORDER_THIN, 'color' => array('rgb'=>'000000')]
                    ]
                ],
                'body' => [
                    'font' => ['size' => 9],
                ]
            ]
        ];

        $this->exportService->loadData($headers, $data, $options);

        $exportContent = $this->exportService->getWriter(WriterType::XLSX)->stream();

        $strToday = (new DateTime())->format('Ymd');
        
        return new Response([], [
            "filename" => "MODEV_LOG_FIJA_{$strToday}.xlsx",
            "type" => "xlsx",
            "content" => $exportContent,
        ]);
    }

    public function exportLogPrepago($ticket){
        $tickets = explode(",", str_replace(" ", "", $ticket));
        $data = $this->repo->getReporteLogPrepago($tickets);

        if(count($data) === 0){
            return Response::respError(["message" => "El ticket no tiene ningun acreditado prepago"]);
        }

        $numberFormat = ['numberFormat' => ['formatCode' => SpreadsheetStyle\NumberFormat::FORMAT_TEXT]];

        $headers = [
            "ticket" => ["label" => "TICKET", 'bodyStyles' => $numberFormat],
            "numero" => ["label" => "NUMERO"],
            "cantidad" => ["label" => "CANTIDAD"],
            "recharge_date" => ["label" => "RECHARGE_DATE"],
            "recarga" => ["label" => "RECARGA"],
            "msisdn_devolver" => ["label" => "MSISDN_DEVOLVER"],
            "usage_str3" => ["label" => "USAGE_STR3"],
        ];

        $options = [
            'sheetIndex' => 0,
            // 'rowType' => 'object',
            'title' => "REP",
            'styles' => [
                'header' => [
                    'font' => ['bold' => true, 'size' => 9],
                    'borders'=> [
                        'allBorders' => ['borderStyle' => SpreadsheetStyle\Border::BORDER_THIN, 'color' => array('rgb'=>'000000')]
                    ]
                ],
                'body' => [
                    'font' => ['size' => 9],
                ]
            ]
        ];

        $this->exportService->loadData($headers, $data, $options);

        $exportContent = $this->exportService->getWriter(WriterType::XLSX)->stream();

        $strToday = (new DateTime())->format('Ymd');
        
        return new Response([], [
            "filename" => "LOG_PREPAGO_FIJA_{$strToday}.xlsx",
            "type" => "xlsx",
            "content" => $exportContent,
        ]);
    }
}
