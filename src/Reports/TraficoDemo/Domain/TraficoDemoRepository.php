<?php

namespace AMovil\Reports\TraficoDemo\Domain;

use AMovil\Shared\Application\FileInput;
use DateTime;

interface TraficoDemoRepository
{
    public function getReportBy(string $username, DateTime $fechaIni, DateTime $fechaFin, FileInput $file): FileInput;
}
