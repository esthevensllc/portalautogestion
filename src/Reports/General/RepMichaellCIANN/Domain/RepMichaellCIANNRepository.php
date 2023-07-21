<?php

namespace AMovil\Reports\General\RepMichaellCIANN\Domain;

use DateTime;

interface RepMichaellCIANNRepository
{
    public function getLlamadasByNumCuenta_Periodo($numCuenta, DateTime $periodo);
    public function getSVAByNumCuenta_Periodo($numCuenta, DateTime $periodo);
    public function getCuotasByNumCuenta_Periodo($numCuenta, DateTime $periodo);
    public function getReporteServiciosByNumCuenta_Periodo($numCuenta, DateTime $periodo);
    public function getReporteOCCByNumCuenta_Periodo($numCuenta, DateTime $periodo);
    public function getRoamingByNumCuenta_Periodo($numCuenta, DateTime $periodo);
    public function getPlanByNumCuenta_Periodo($numCuenta, DateTime $periodo);
    public function getInfoByNumCuenta($numCuenta);
}
