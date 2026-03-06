<?php

namespace AMovil\Reports\ExtraccionDevFija\Modev\Domain;

interface ModevFijaRepository
{
    public function getReporteModev(array $tickets);
    public function getReporteLogPrepago(array $tickets);
    public function getReporteModevMantenimiento(array $tickets);
}
