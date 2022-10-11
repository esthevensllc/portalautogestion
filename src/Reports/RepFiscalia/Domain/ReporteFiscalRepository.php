<?php

namespace AMovil\Reports\RepFiscalia\Domain;

use DateTime;

interface ReporteFiscalRepository
{
    public function getReporteByMsisdn_Periodos(string $msisdn, DateTime $periodo1, DateTime $periodo2);
    public function msisdnExists($msisdn, DateTime $fechaIni, DateTime $fechaFin): bool;
    public function hasRecords(string $msisdn, DateTime $fechaIni, DateTime $fechaFin): bool;
}
