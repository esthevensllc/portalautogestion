<?php

namespace AMovil\Reports\ExtraccionDevolucion\MODEV\Domain;

interface MODEVRepository
{
    public function getReporteByTicketAndDepartamento($ticket, $departamento);
}
