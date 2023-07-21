<?php

namespace AMovil\Reports\DAPU\SuspensionServicio\Domain;

use DateTime;

interface SuspensionServicioRepository
{
    public function getByMsisdn($msisdn,$dni, DateTime $fecha1, DateTime $fecha2);
}
