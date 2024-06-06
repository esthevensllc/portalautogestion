<?php

namespace AMovil\Reports\DAPU\MotivosBloqueoDesbloqueo\Domain;

interface MotivosBloqueoDesbloqueoRepository
{
    public function getByImei($imei);
}
