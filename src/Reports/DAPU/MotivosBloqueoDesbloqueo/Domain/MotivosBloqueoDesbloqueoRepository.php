<?php

namespace AMovil\Reports\DAPU\MotivosBloqueoDesbloqueo\Domain;

interface MotivosBloqueoDesbloqueoRepository
{
    public function getByLinea($linea);
    public function getByImei($imei);
}
