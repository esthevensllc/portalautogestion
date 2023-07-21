<?php

namespace AMovil\Reports\ReportLog\Domain;

use DateTime;

interface ReportLogRepository
{
    public function save($name, $direccion, $area, $contacto, $codigo_c, $responsable, $file, DateTime $ini, DateTime $fin, $lat, $estado, $mensaje, $trac_name);
    public function getByCriteria(array $filters, $order = [], $offset=0, $limit=10);
}
