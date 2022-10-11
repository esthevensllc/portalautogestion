<?php

namespace AMovil\Reports\RepDetLlamadas\Domain;

use DateTime;

interface DetalleLlamadasRepository
{
    public function getReporteByPeriodo_CodCliente_tipo(DateTime $periodo1, DateTime $periodo2, array $cod_cliente, string $tipo_reporte);
    public function getReporteByPeriodo_NumDocumento_tipo(DateTime $periodo1, DateTime $periodo2, array $num_documento, string $tipo_reporte);
    public function getReporteByPeriodo_NumCuenta_tipo(DateTime $periodo1, DateTime $periodo2, array $num_cuenta, string $tipo_reporte);
    public function getReporteByPeriodo_Lineas_tipo(DateTime $periodo1, DateTime $periodo2, array $lineas, string $tipo_reporte);
}
