<?php

namespace AMovil\Reports\RepRecargas\Domain;

use DateTime;

interface ReporteRecargasRepository
{
    public function getDetalle(array $lineas, DateTime $fecha1, DateTime $fecha2);
    public function getExtras(array $lineas, DateTime $fecha1, DateTime $fecha2);
}

