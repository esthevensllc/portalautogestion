<?php

namespace AMovil\Reports\DAPU\LogBiometria\Domain;

interface LogBiometriaRepository
{
    public function getByDni_Periodo($dni, $periodo);
}
