<?php

namespace AMovil\Reports\General\BloqueoImei\Domain;

use DateTime;

interface BloqueoImeiRepository
{
    public function getReporte($id, $filename, $imeis, DateTime $fechaIni, DateTime $fechaFin);
    public function getReporteLogByCriteria($filters);
    public function saveReporteLog($id, $username, $filename, $nRegistros);
    public function deleteReporteLog($id);
}
