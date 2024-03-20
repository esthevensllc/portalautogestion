<?php

namespace AMovil\Reports\ClientesMacSn\Domain;

use DateTime;

interface ClientesMacSnRepository
{
    public function getClientesMacSnSummary();
    public function getReporte(array $macs, DateTime $fecha);
}
