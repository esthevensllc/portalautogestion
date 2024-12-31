<?php

namespace AMovil\Reports\ExtraccionDevFija\Modev\Domain;

interface ModevFijaRepository
{
    public function getReporteModev(array $tickets);
}
