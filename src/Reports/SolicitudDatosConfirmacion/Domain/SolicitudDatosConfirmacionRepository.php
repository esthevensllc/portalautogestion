<?php

namespace AMovil\Reports\SolicitudDatosConfirmacion\Domain;

use DateTime;

interface SolicitudDatosConfirmacionRepository
{
    public function getTable($filter1,$filter2,$filter3,$filter4);
    public function export($filter1,$filter2,$filter3,$filter4);
}
