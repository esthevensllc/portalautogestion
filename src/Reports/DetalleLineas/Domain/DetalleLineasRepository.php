<?php

namespace AMovil\Reports\DetalleLineas\Domain;

use AMovil\Shared\Application\FileInput;

interface DetalleLineasRepository
{
    public function getReportBy(string $username, FileInput $file): FileInput;
}
