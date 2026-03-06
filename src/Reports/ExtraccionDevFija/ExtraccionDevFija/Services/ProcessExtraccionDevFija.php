<?php

namespace AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaRepository;
use AMovil\Reports\ExtraccionDevolucion\TablaInteres\Domain\TablaInteresRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProcessExtraccionDevFija
{
    private $repo;
    private $exportService;
    private $tablaInteresRepo;
    public function __construct(ExtraccionDevFijaRepository $repo, ExportService $exportService, TablaInteresRepository $tablaInteresRepo)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->tablaInteresRepo = $tablaInteresRepo;
    }

    public function processAndGetGruposUsuario($departamentos, $provincias, $distritos, $planos,
    $ticket, $servicioAfectado, $fechaIni, $horaIni, $fechaFin, $horaFin, $mesesInteres){
        $dtLastDay = new DateTime();
        $dtLastDay->modify("-1 day");
        if (!$this->tablaInteresRepo->existsIn($dtLastDay)) {
            throw new Exception("La tabla de interes no esta actualizada");
        }

        $arrayDistritos = [];
        foreach($departamentos as $index => $departemento){
            $arrayPlanos = explode(",", str_replace(" ", "", $planos[$index]));
            $arrayDistritos[] = [$departemento, $provincias[$index], $distritos[$index], $arrayPlanos];
        }

        $dtFechaIni = DateTime::createFromFormat("Y-m-d H:i:s", "{$fechaIni} {$horaIni}");
        $dtFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", "{$fechaFin} {$horaFin}");

        if(count($arrayDistritos) === 0){
            throw new Exception("Se debe agregar como minimo un departamento, provincia y distrito");
        }
        /*$departemento = $arrayDistritos[0][0];
        foreach($arrayDistritos as $row){
            if($departemento !== $row[0]){
                throw new Exception("No se puede procesar dos departamentos diferentes");
            }
        }*/
        $reportCount = $this->repo->countReportByTicketDepartamento($ticket);
        if($reportCount > 0){
            throw new Exception("Ya se tiene procesado el TK_{$ticket}");
        }

        $usuarios = $this->repo->processAndGetGruposUsuario(
            $arrayDistritos,
            $ticket, $servicioAfectado, $dtFechaIni, $dtFechaFin,
            $mesesInteres
        );
        return new Response([], [
            ["id" => 1, "label" => "{$usuarios->usuarios_activos} usuarios activos"],
            ["id" => 2, "label" => "{$usuarios->usuarios_con_trafico} usuarios con trafico"],
        ]);
    }

    public function processEnd($ticket, $grupoUsuarios, int $filterFlag): Response
    {
        // grupoUsuarios (1=usuarios activos, 2=usuarios con trafico)
        $reportCount = $this->repo->countReportByTicketDepartamento($ticket);
        if($reportCount > 0){
            throw new Exception("Ya se tiene procesado el TK_{$ticket}");
        }

        $data = $this->repo->processEnd($ticket, $grupoUsuarios, $filterFlag);

        $headers = [
            "ticket" => ["label" => "TICKET"],
            "codcli" => ["label" => "CODCLI"],
            "nomcli" => ["label" => "NOMCLI"],
            "nro_doc" => ["label" => "NRO_DOC"],
            "tipdoc" => ["label" => "TIPDOC"],
            "numero" => ["label" => "NUMERO"],
            "cid" => ["label" => "CID"],
            "familia" => ["label" => "FAMILIA"],
            "codsrv" => ["label" => "CODSRV"],
            "dscsrv" => ["label" => "DSCSRV"],
            "idplano" => ["label" => "IDPLANO"],
            "fec_ini_incidencia" => ["label" => "FEC_INI_INCIDENCIA"],
            "fec_fin_incidencia" => ["label" => "FEC_FIN_INCIDENCIA"],
            "minutos_afectacion" => ["label" => "MINUTOS_AFECTACION"],
            "dpto" => ["label" => "DPTO"],
            "provincia" => ["label" => "PROVINCIA"],
            "distrito" => ["label" => "DISTRITO"],
            "moneda" => ["label" => "MONEDA"],
            "cr_neto" => ["label" => "CR_NETO"],
        ];
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
        $this->exportService->loadData($headers, $data, $options);
        $content = $this->exportService->getWriter(WriterType::CSV)->getOutput();

        return new Response([], [
            "filename" => "WRK_DEVOL_INC_MASIV.csv",
            "type" => "csv",
            "content" => $content,
        ]);
    }
}
