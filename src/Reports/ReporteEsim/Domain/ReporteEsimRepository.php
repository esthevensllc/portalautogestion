<?php

namespace AMovil\Reports\ReporteEsim\Domain;

interface ReporteEsimRepository
{
    public function getReporte(string $nintex, array $data);
    public function nintexIsProcessed($nintex);
}
