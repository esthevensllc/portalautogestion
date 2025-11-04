<?php

namespace AMovil\Reports\AlertaMml\Domain;

use DateTime;

interface AlertaMmlRepository
{
    public function getReport($baseFlag, DateTime $fechaIni, DateTime $fechaFin, int $whiteList);
    public function saveLog($nintex, $base, DateTime $fechaIni, DateTime $fechaFin, DateTime $createAt, $filename, int $whiteList);
    public function getLogs();
    public function getBlackAndWhiteListSummary();
}
