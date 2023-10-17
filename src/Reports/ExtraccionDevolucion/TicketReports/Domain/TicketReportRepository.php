<?php

namespace AMovil\Reports\ExtraccionDevolucion\TicketReports\Domain;

interface TicketReportRepository
{
    public function deleteBy($ticket, $departamento);
    public function reportIsConfirmed($ticket, $departamento);
    public function confirmBy($ticket, $departamento);
    public function findInputFor($ticket, $departamento);
    public function getTickets();
    public function getDepartamentos();
    public function findByTicketAndDepartamento($ticket, $departamento);
}
