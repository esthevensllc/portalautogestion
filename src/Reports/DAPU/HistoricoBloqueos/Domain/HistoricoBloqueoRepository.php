<?php

namespace AMovil\Reports\DAPU\HistoricoBloqueos\Domain;

interface HistoricoBloqueoRepository
{
    public function getByImei($imei);
}
