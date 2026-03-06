<?php

namespace AMovil\Reports\OltCmts\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository as InformeFallasRepository;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\InformeTipoReporte;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\AprobarReport;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\CargarReporte;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services\GetUsuariosExtraccion;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use DateTime;

class ProcessExtraccion
{
    private $cargaInformeFallas;
    private $getUsuariosExtraccion;
    private $processExtraccion;
    private $informeFallasrepo;
    private $saveReportLog;

    const MESES_INTERES = 24;
    const MINUTOS_USUARIOS = 3;

    public function __construct(
        CargarReporte $cargaInformeFallas,
        GetUsuariosExtraccion $getUsuariosExtraccion,
        ProcessExtraccion $processExtraccion,
        InformeFallasRepository $informeFallasrepo,
        SaveReportLog $saveReportLog
    ) {
        $this->cargaInformeFallas = $cargaInformeFallas;
        $this->getUsuariosExtraccion = $getUsuariosExtraccion;
        $this->processExtraccion = $processExtraccion;
        $this->informeFallasrepo = $informeFallasrepo;
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke($num_reporte, $ticket, $excel, $corteFechaIni, $corteHoraIni, $corteFechaFin, $corteHoraFin, $userIpAddress)
    {
        if ($ticket === null) {
            return new Response(["message" => "El ticket ingresado no es válido"]);
        }
        if (count($this->informeFallasrepo->findInputFor($num_reporte)) > 0) {
            return new Response(["message" => "El numero de reporte ya existe"]);
        }
        if ($this->informeFallasrepo->getInputByTicket($ticket) !== null) {
            return new Response(["message" => "El ticket ya existe"]);
        }
        $detalleExtraccion = [
            [
                "celdas" => "0",
                "distritos" => "-,-,-",
                "corteFechaIni" => $corteFechaIni." ".$corteHoraIni,
                "corteFechaFin" => $corteFechaFin." ".$corteHoraFin,
            ]
        ];
        $this->cargaInformeFallas->__invoke($num_reporte, InformeTipoReporte::BY_MSISDN2, $excel, $detalleExtraccion, $userIpAddress);

        $this->informeFallasrepo->revisado($num_reporte);
        $this->informeFallasrepo->aprobar($num_reporte, $ticket);
        $this->informeFallasrepo->updateTicketForInputMsisdn($num_reporte, $ticket);

        $input = $this->getUsuariosExtraccion->getInputs($num_reporte, "-")->data();
        $minutos_usuarios = 3;
        $distritos = [];
        foreach($input->distritos as $row){
            $distritos[] = "{$row->departamento},{$row->provincia},{$row->distrito}";
        }
        $this->processExtraccion->__invoke(
            1,
            1,
            implode(",", $input->celdas),
            implode("\n", $distritos),
            null,
            null,
            null,
            $input->ticket,
            ProcessExtraccion::MESES_INTERES,
            $input->corte_fecha_ini,
            $input->corte_fecha_fin,
            $minutos_usuarios,
            $userIpAddress
        );
        $response = $this->processExtraccion->__invoke(
            2,
            1,
            implode(",", $input->celdas),
            implode("\n", $distritos),
            null,
            null,
            null,
            $input->ticket,
            ProcessExtraccion::MESES_INTERES,
            $input->corte_fecha_ini,
            $input->corte_fecha_fin,
            $minutos_usuarios,
            $userIpAddress
        )->toArray();
        return Response::respData(["ticket" => $ticket]);
    }

    private function reportLog($allFilename, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        $filename = "EXTRACCION_".$ini->format('YmdHis').".xlsx";
        $data = array_merge([
            'name' => 'EXTRACCION',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => 'extraccion-devolucion',
            "filename" => $allFilename !== null ? $filename : null
        ], $extra_data);
        $this->saveReportLog->__invoke($data, $allFilename, 'EXTRACCION');
    }
}
