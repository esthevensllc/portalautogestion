<?php

namespace AMovil\Reports\DetallePlanes\Planes\Domain;

interface DetallePlanRepository
{
    public function getByNumDocumento(string $numDocumento);
    public function getByNumCuenta(string $numCuenta);
    public function getByLineas(array $lineas);
    public function getTiposInput();
}
