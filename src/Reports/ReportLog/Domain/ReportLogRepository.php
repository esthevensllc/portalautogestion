<?php

namespace AMovil\Reports\ReportLog\Domain;

use DateTime;

interface ReportLogRepository
{
    public function save($name, $direccion, $area, $contacto, $responsable, $file, DateTime $ini, DateTime $fin, $lat, $estado, $mensaje);
}
