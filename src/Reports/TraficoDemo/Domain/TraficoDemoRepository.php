<?php

namespace AMovil\Reports\TraficoDemo\Domain;

use AMovil\Shared\Application\FileInput;

interface TraficoDemoRepository
{
    public function getReportBy(string $username, int $year, int $month, FileInput $file): FileInput;
}
