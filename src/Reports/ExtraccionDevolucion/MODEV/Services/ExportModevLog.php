<?php

namespace AMovil\Reports\ExtraccionDevolucion\MODEV\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository as InformeFallaRepository;
use AMovil\Reports\ExtraccionDevolucion\MODEV\Domain\MODEVRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportModevLog
{
    private $repo;
    private $exportService;
    private $informeRepo;

    public function __construct(MODEVRepository $repo, ExportService $exportService, InformeFallaRepository $informeRepo)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->informeRepo = $informeRepo;
    }

    public function __invoke($ticket)
    {
        $tickets = explode(",", str_replace(" ", "", $ticket));
        // $informeInput = $this->informeRepo->getInputByTicket($ticket);
        /*$validation = $this->repo->validateRecargas($tickets);
        foreach($validation as $row){
            $this->repo->saveRecargasNoCorrectas($row['ticket']);
        }*/
        $tipoReporte = null;
        if (count($tickets) > 0) {
            $informe = $this->informeRepo->getInputByTicket($tickets[0]);
            if ($informe === null) {
                return new Response(["message" => "El ticket no existe"]);
            }
            $tipoReporte = $informe->tipo_reporte;
        }
        $data = $this->repo->getReporteModev($tipoReporte, $tickets);

        $headers = [
            "ticket" => ["label" => "TICKET"],
            "tipo_documento" => ["label" => "TIPO_DOCUMENTO"],
            "nro_documento" => ["label" => "NRO_DOCUMENTO"],
            "id_cliente" => ["label" => "ID_CLIENTE"],
            "msisdn" => ["label" => "MSISDN"],
            "Servicio_Analizado" => ["label" => "SERVICIO_ANALIZADO"],
            "modo_contratacion" => ["label" => "MODO_CONTRATACION"],
            "cargo_linea_igv" => ["label" => "CARGO_LINEA_IGV"],
            "minutos" => ["label" => "MINUTOS"],
            "monto_devolver" => ["label" => "MONTO_DEVOLVER"],
            "monedas" => ["label" => "MONEDAS"],
            "fecha_devolucion" => ["label" => "FECHA_DEVOLUCION"],
            "nro_recibo" => ["label" => "NRO_RECIBO"],
            "estado" => ["label" => "ESTADO"],
            "fecha_de_baja_del_servicio" => ["label" => "FECHA_BAJA_SERVICIO"],
            "nombre_o_razon_social" => ["label" => "NOMBRE_O_RAZON_SOCIAL"],
            "lugar_donde_cobrar" => ["label" => "LUGAR_DONDE_COBRAR"],
            "requisitos_para_el_cobro" => ["label" => "REQUISITOS_PARA_EL_COBRO"],
            "comunicacion" => ["label" => "COMUNICACION"],
            "medio_de_comunicacion" => ["label" => "MEDIO_DE_COMUNICACION"],
            "motivo_solo_cuando_no_corresponde" => ["label" => "MOTIVO_SOLO_CUANDO_NO_CORRESPONDE"],
            "comentarios" => ["label" => "COMENTARIOS"],
            "liberado" => ["label" => "LIBERADO"],
            "recharge_date" => ["label" => "RECHARGE_DATE"],
            "served_number" => ["label" => "SERVED_NUMBER"],
            "recharge_qty" => ["label" => "RECHARGE_QTY"],
            "usage_str3" => ["label" => "USAGE_STR3"],
            "fecha_carga" => ["label" => "FECHA_CARGA"],
        ];

        $options = [
            'sheetIndex' => 0,
            'rowType' => 'array',
            'title' => "Reporte",
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
            "filename" => "MODEV_LOG.xlsx",
            "type" => "xlsx",
            "content" => $exportContent,
        ]);
    }
}
