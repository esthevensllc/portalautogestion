<?php

namespace AMovil\Reports\ExtraccionDevFija\Modev\Services;

use AMovil\Reports\ExtraccionDevFija\Modev\Domain\ModevFijaRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportModevLogFija
{
    private $repo;
    private $exportService;

    public function __construct(ModevFijaRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($ticket)
    {
        $tickets = explode(",", str_replace(" ", "", $ticket));
        $data = $this->repo->getReporteModev($tickets);

        $headers = [
            "ticket" => ["label" => "TICKET"],
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
        
        return new Response([], [
            "filename" => "MODEV_LOG_FIJA.xlsx",
            "type" => "xlsx",
            "content" => $exportContent,
        ]);
    }
}
