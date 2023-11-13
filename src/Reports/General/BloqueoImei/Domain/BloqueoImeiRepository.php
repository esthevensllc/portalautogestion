<?php

namespace AMovil\Reports\General\BloqueoImei\Domain;

use DateTime;

interface BloqueoImeiRepository
{
    public function getReporte($id, $tipoBusquedaId, $filename, $imeis, DateTime $fechaIni, DateTime $fechaFin);
    public function getReporteLogByCriteria($filters);
    public function saveReporteLog($id, $tipoBusquedaId, $username, $filename, $nRegistros);
    public function deleteReporteLog($id);
    
    public function getReporteFromBaseImei(DateTime $fechaIni, DateTime $fechaFin);
    public function getAutomaticReportLogByCriteria($filters);
    public function deleteAutomaticReportLog($id);
    public function importBaseImeiAutomatico($id, $filename, $imeis);
    public function deleteBaseImeiAutomatico($imeis);
    public function saveAutomaticReportLog($id, $username, $filename, $nRegistros);
    public function saveAutomaticReport($id, $filename, $nRegistros, $sizeBytes, DateTime $fecha);
    public function getAutomaticReportByCriteria($filters);
    public function saveConfig(bool $generateReport);
    public function getConfig();

    public function getControlEirImeiByCriteria($filters);
    public function getReporteArray($id, $tipoBusquedaId, $imeis, DateTime $fechaIni, DateTime $fechaFin);
}
