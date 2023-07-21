<?php

namespace AMovil\Reports\CajaArequipa\GBOtorgados\Domain;

use DateTime;

interface GBOtorgadosRepository
{
    public function getByNumCuentaAndPeriodo($numCuenta, DateTime $periodo);
}
