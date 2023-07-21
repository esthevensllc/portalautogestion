<?php

namespace AMovil\Reports\Visanet\Domain;

use DateTime;

interface VisanetRepository
{
    public function getFacturaDetalladaByNumCuenta_Periodo(array $numCuenta, DateTime $periodo);
    public function getConsumoDatosByNumCuenta_Periodo(array $numCuenta, DateTime $periodo);
}
