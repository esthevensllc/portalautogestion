<?php

namespace AMovil\Reports\DAPU\VentasLinea\Domain;

interface VentasLineaRepository
{
    public function getByDni_Fono_Periodo($dni, $fono);
    public function getPrepagoByDni_Fono_Periodo($dni, $fono);
}
