<?php

namespace AMovil\Reports\CargosFijosServiciosActivos\Domain;

use DateTime;

interface DetalleRepository
{
    public function getReporte(string $tipo_input,array $lineas, $excel_data, $num_doc, $num_cuenta, $sn);
    public function saveLogReporteTemp(string $filename, DateTime $createAt, int $size_bytes);
    public function getByCriteria(array $filters, $sortBy = [], $offset=0, $limit=0);
    public function deleteLogReporteTemp();
}
