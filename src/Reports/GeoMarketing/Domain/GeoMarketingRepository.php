<?php

namespace AMovil\Reports\GeoMarketing\Domain;

use DateTime;

interface GeoMarketingRepository
{
    public function getReport(DateTime $fechaIni, DateTime $fechaFin);
}
