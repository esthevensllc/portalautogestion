<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain;

use DateTime;

interface ExtraccionRepository
{
    public function getReporte(array $celdas, array $provincias, DateTime $fechaIni, DateTime $fechaFin, $ticketOsiptel, DateTime $fechaInteres, DateTime $corteFechaIni, Datetime $corteFechaFin);
    public function getReporteWithoutValidation(array $celdas, array $provincias, DateTime $fechaIni, DateTime $fechaFin, $ticketOsiptel, DateTime $fechaInteres, DateTime $corteFechaIni, Datetime $corteFechaFin);
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
    public function getReporteMsisdn($ticket);
    public function getReporteDiligenciasWebCorreo($tickets);
    public function getReporteDiligenciasWebDocumento($tickets);
    public function getTicketsWithDevolucionWeb($tickets);
    public function saveReporteValidacionRecarga($tickets);

    public function updateReporte($ticket, $departamento, $data);
    public function getPrepagoLog($tickets);
    public function updateNumAcreditadosPrepagoByTicket($tickets);
    public function saveAcreditacionPrepago($ticket, $departamento, $data);
}
