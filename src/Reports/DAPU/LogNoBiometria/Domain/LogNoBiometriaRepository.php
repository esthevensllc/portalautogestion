<?php

namespace AMovil\Reports\DAPU\LogNoBiometria\Domain;

use DateTime;

interface LogNoBiometriaRepository
{
    public function getByDniAndPeriodo($dni, DateTime $periodo);
}
