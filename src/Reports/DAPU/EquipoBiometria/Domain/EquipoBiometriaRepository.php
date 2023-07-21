<?php

namespace AMovil\Reports\DAPU\EquipoBiometria\Domain;

interface EquipoBiometriaRepository
{
    public function getByDni_Fono_Periodo($dni, $fono, $periodo);
}
