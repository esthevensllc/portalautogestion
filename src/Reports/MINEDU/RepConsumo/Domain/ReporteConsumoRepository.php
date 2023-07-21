<?php

namespace AMovil\Reports\MINEDU\RepConsumo\Domain;

use DateTime;

interface ReporteConsumoRepository
{
    public function loadPlanDatos(array $data);
    public function getByConsumoPLanDatos_Tipo(DateTime $fecha1, DateTime $fecha2, string $tipo_reporte);
    //public function getByNumCuenta_tipo(DateTime $fecha1, DateTime $fecha2, string $num_cuenta, string $tipo_reporte);
}
