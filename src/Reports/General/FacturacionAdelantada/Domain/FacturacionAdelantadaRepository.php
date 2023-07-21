<?php

namespace AMovil\Reports\General\FacturacionAdelantada\Domain;

use DateTime;

interface FacturacionAdelantadaRepository
{
    public function getReporteByDates($cuenta, $numeroFactura, DateTime $fechaIni, DateTime $fechaFin);
    public function getReporteByPeriodo($cuenta, $numeroFactura, DateTime $periodo);
    public function getReporteConsolidadoByDates($cuenta, $numeroFactura, string $unidad_trafico_id, string $unidad_consumo_id, DateTime $fechaIni, DateTime $fechaFin);
}
