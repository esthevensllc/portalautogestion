<?php

namespace AMovil\Reports\DAPU\CambioTitularidad\Domain;

interface CambioTitularidadRepository
{
    public function getByLinea(string $linea);
}
