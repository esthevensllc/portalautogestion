<?php

namespace AMovil\Reports\DetalleTasado\Domain;

use DateTime;

interface DetalleTasadoRepository
{
    public function getReporte($numerosCuenta, DateTime $fechaIni, DateTime $fechaFin);
}
