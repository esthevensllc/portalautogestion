<?php

namespace AMovil\Reports\DAPU\LogNoBiometria\Domain;

interface LogNoBiometriaRepository
{
    public function getByDniAndPeriodo($dni, $periodo);
}
