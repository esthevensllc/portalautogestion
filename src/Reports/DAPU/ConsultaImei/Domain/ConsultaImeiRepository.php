<?php

namespace AMovil\Reports\DAPU\ConsultaImei\Domain;

use DateTime;

interface ConsultaImeiRepository
{
    public function getByMsisdnAndFecha($msisdn, DateTime $fecha);
}
