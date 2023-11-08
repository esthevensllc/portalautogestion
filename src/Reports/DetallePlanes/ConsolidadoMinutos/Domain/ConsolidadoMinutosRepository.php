<?php

namespace AMovil\Reports\DetallePlanes\ConsolidadoMinutos\Domain;

use DateTime;

interface ConsolidadoMinutosRepository
{
    public function getReporteByNumCuentaAndPeriodo(string $numCuenta, DateTime $periodo);
}
