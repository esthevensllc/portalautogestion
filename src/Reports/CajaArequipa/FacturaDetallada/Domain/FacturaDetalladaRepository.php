<?php

namespace AMovil\Reports\CajaArequipa\FacturaDetallada\Domain;

use DateTime;

interface FacturaDetalladaRepository
{
    public function getCuotasByNumCuentaAndPeriodo($numCuenta, DateTime $periodo);
    public function getServiciosByNumCuentaAndPeriodo($numCuenta, DateTime $periodo);
    public function getOCCByNumCuentaAndPeriodo($numCuenta, DateTime $periodo);
    public function getRoamingByNumCuentaAndPeriodo($numCuenta, DateTime $periodo);
    public function getPlanByNumCuentaAndPeriodo($numCuenta, DateTime $periodo);
}
