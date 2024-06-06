<?php

namespace AMovil\Reports\DAPU\ServicioMovil\Domain;

use DateTime;

interface ServicioMovilRepository
{
    public function getByMsisdnFecha(string $imei, DateTime $fecha);
}
