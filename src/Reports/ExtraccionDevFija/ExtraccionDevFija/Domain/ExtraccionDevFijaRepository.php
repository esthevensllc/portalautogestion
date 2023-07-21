<?php

namespace AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain;

use DateTime;

interface ExtraccionDevFijaRepository
{
    public function process($distritos, $ticket, $servicioAfectado, DateTime $fechaIni, DateTime $fechaFin, $mesesInteres);
}
