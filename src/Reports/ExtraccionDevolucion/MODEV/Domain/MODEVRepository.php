<?php

namespace AMovil\Reports\ExtraccionDevolucion\MODEV\Domain;

interface MODEVRepository
{
    public function getReporteByTicketAndDepartamento($ticket, $departamento);
    public function validateRecargas($ticket);
    public function saveRecargasNoCorrectas($ticket);
    public function getReporteModev($ticket);
}
