<?php

namespace AMovil\Reports\RepDetConsumoNF\Domain;

use DateTime;

interface DetalleConsumoNFRepository
{
    public function getReporteDetallado(string $numCuenta, DateTime $fechaIni, DateTime $fechaFin);
}
