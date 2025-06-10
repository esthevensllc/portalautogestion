<?php

namespace AMovil\Reports\ExtraccionDevolucion\MODEV\Domain;

interface MODEVRepository
{
    public function getReporteByTicketAndDepartamento($tipoReporte, $ticket, $departamento);
    public function validateRecargas(array $ticket);
    public function saveRecargasNoCorrectas($ticket);
    public function getReporteModev(array $ticket);
}
