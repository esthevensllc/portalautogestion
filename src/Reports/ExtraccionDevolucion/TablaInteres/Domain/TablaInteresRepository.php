<?php

namespace AMovil\Reports\ExtraccionDevolucion\TablaInteres\Domain;

use DateTime;

interface TablaInteresRepository
{
    public function insert(DateTime $fecha, $tasa, $factorDiario, $factorAcumulado);
    public function getMaxFechaInteres();
    public function getFactorAcumuladoByFecha(int $limit = 10);
}
