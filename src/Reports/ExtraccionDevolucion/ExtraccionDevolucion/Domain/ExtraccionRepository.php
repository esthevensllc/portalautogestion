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
    public function getReporteDiligenciasWebCorreo($ticket);
    public function getReporteDiligenciasWebDocumento($ticket);
    public function saveReporteValidacionRecarga($ticket);

    public function updateReporte($ticket, $departamento, $data);
    public function updateNumAcreditadosPrepagoByTicket($ticket);
    public function saveAcreditacionPrepago($ticket, $departamento, $data);
}
