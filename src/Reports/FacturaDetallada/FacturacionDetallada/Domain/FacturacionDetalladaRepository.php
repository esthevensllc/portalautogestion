<?php

namespace AMovil\Reports\FacturaDetallada\FacturacionDetallada\Domain;

use DateTime;

interface FacturacionDetalladaRepository
{
    public function getByNumCuentaAndPeriodo(array $numerosCuenta, DateTime $periodo);
    public function getTiposInput();
}
