<?php

namespace AMovil\Reports\RepDetConsumoNF\Domain;

use DateTime;

interface DetalleConsumoNFRepository
{
    public function getReporteDetallado(string $numCuenta, DateTime $fechaIni, DateTime $fechaFin);
    public function getReporteDetalladoByLineas(array $lineas, DateTime $fechaIni, DateTime $fechaFin);
}
