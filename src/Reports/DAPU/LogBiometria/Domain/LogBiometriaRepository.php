<?php

namespace AMovil\Reports\DAPU\LogBiometria\Domain;

use DateTime;

interface LogBiometriaRepository
{
    public function getByDniAndPeriodo($dni, DateTime $periodo);
}
