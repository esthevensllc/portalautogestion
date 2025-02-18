<?php

namespace AMovil\Reports\DAPU\VentasFija\Domain;

interface VentasFijaRepository
{
    public function getByNumDocumento(array $values);
}
