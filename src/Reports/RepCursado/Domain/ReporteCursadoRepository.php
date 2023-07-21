<?php

namespace AMovil\Reports\RepCursado\Domain;

use DateTime;

interface ReporteCursadoRepository
{
    public function getReporteByNumCuenta($num_cuenta, DateTime $fecha1, DateTime $fecha2);
    public function getReporteByCodCliente($cod_cliente, DateTime $fecha1, DateTime $fecha2);
    public function getReporteByNumDocumento($num_documento, DateTime $fecha1, DateTime $fecha2);
    public function getReporteByLineas(array $lineas, DateTime $fecha1, DateTime $fecha2);

    public function getCicloByNumCuenta($num_cuenta, DateTime $fecha1, DateTime $fecha2);
    public function getCicloByCodCliente($cod_cliente, DateTime $fecha1, DateTime $fecha2);
    public function getCicloByNumDocumento($num_documento, DateTime $fecha1, DateTime $fecha2);
    public function truncateConsolidado();
    public function getConsolidado();
    public function getPeriodosDisponibles();
}
