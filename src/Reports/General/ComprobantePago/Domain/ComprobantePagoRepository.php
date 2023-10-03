<?php

namespace AMovil\Reports\General\ComprobantePago\Domain;

interface ComprobantePagoRepository
{
    public function getReporte(array $values);
}
