<?php

namespace AMovil\Reports\GeoMarketing\Domain;

use DateTime;

interface GeoMarketingRepository
{
    public function getReport(DateTime $fechaIni, DateTime $fechaFin);
    public function saveLog($nintex, $base, DateTime $fechaIni, DateTime $fechaFin, DateTime $createAt, $filename);
}
