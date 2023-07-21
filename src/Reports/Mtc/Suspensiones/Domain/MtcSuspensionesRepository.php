<?php

namespace AMovil\Reports\Mtc\Suspensiones\Domain;

use DateTime;

interface MtcSuspensionesRepository
{
    public function getReporte(DateTime $periodo, array $suspensiones);
    public function generateReporteSuspensiones(DateTime $periodo, array $suspensiones);
    public function generateReporteTitularidad(DateTime $periodo, array $suspensiones);
    public function generateReporteMensual(DateTime $periodo, array $suspensiones);
    public function generateReporteNotificacion(DateTime $periodo, array $suspensiones);
    public function getPlantilla();
    public function getCountSuspensionesByAsncEstado($estado);
    public function getLineas();
    public function getLineasMoviles();
    public function getLineasFijas();
    public function getReporteTitularidad();
    public function getCountRepTitularidadByAsncTipoTitular($tipo);
    public function getReporteMensual();
    public function getReporteNotificacion();
}
