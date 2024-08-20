<?php

namespace AMovil\Reports\ReporteLineasEnrutadas\Domain;

use DateTime;

interface ReporteLineasEnrutadasRepository
{
    public function getReporte(array $macs);
}
