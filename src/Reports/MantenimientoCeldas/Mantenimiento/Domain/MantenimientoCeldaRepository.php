<?php

namespace AMovil\Reports\MantenimientoCeldas\Mantenimiento\Domain;

use DateTime;

interface MantenimientoCeldaRepository
{
    public function process(array $celdas, array $provincias, DateTime $fechaIni, DateTime $fechaFin, $ticketOsiptel, DateTime $fechaInteres, DateTime $corteFechaIni, Datetime $corteFechaFin);
    public function getReporte($ticket, $departamento);
    public function delete($ticket, $departamento);

    public function getInputs($ticket, $departamento);
}
