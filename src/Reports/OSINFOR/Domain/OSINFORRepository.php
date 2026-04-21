<?php

namespace AMovil\Reports\OSINFOR\Domain;

interface OSINFORRepository
{
    public function getAvailableRange(string $codigoCliente): array;

    public function existsByYearAndWeek(string $codigoCliente, string $anio, string $semana): bool;

    public function getReporte(string $codigoCliente, string $anio, string $semana): array;
}
