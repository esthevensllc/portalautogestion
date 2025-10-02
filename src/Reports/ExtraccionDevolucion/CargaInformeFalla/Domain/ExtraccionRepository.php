<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain;

use DateTime;

interface ExtraccionRepository
{
    public function getReporte(array $celdas, array $provincias, DateTime $fechaIni, DateTime $fechaFin, $ticketOsiptel, DateTime $fechaInteres, DateTime $corteFechaIni, Datetime $corteFechaFin);
    public function getReporte2(array $celdas, array $provincias, DateTime $fechaIni, DateTime $fechaFin, $ticketOsiptel, DateTime $fechaInteres, DateTime $corteFechaIni, Datetime $corteFechaFin);
    public function getReporteMontoDevolver(Datetime $fechaInteres, Datetime $corteFechaIni);
    public function ticketAndDepartamentoExistsInConsolidado($ticket, $departamento);
    public function getBaseAbonados();
    public function getBaseDevolucionPostpago(Datetime $fechaCorteIni);
    public function getBaseDevolucionPrepago(Datetime $fechaCorteIni);
    public function saveResultsInLog(string $log_id, Datetime $fechaCorteIni);

    public function getUsuariosAfectadosBy($ticket, $departamento);
    public function getPostpagoBy($ticket, $departamento);
    public function getPrepagoBy($ticket, $departamento);

    public function getReportes();
    public function updateReporte($numero, $excel);
    public function findInputFor($numero);
    public function findInformeByTicket($ticket);
    public function deleteRecord($numero);
    public function registerStatusChanges(string $numReporte, ?string $departamento, string $username, string $ipAddress, string $status, DateTime $fecha);
    public function aprobar($id,$ticket);
    public function desaprobar($id);
    public function enEspera($id);
    public function revisado($id);
    public function procesado($id);
    public function enEjecucionPre($id);
    public function enEsperaPre($id);
    public function enEjecucionPost($id);
    public function enEsperaPost($id);
    public function getReportesSnRevisado();
    public function getReportesAprobados();
    public function getReportesProcesados();
    public function saveAcreditacionPrepago($ticket, $departamento, $data);
    public function saveReportInputs($num_reporte, $tipoReporte, array $celdas, array $provincias, DateTime $fechaIni, DateTime $fechaFin, $ticketOsiptel, DateTime $fechaInteres, DateTime $corteFechaIni, Datetime $corteFechaFin);
    public function saveInputMsisdn($num_reporte, $data);
    public function updateTicketForInputMsisdn($num_reporte, $ticket);
    public function getInputByTicket($ticket);
    public function getInputByNumReporte_Departamento($num_reporte, $departamento);
    public function getDepartamentosByNumReporte($num_reporte);
    public function getReportesByCriteria($filters);
}
