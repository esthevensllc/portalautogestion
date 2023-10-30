<?php

namespace AMovil\Reports\DetallePlanes\FacturacionDetallada\Domain;

use DateTime;

interface FacturacionDetalladaRepository
{
    public function getByNumCuentaAndPeriodo(array $numerosCuenta, DateTime $periodo);
    public function getTiposInput();
}
