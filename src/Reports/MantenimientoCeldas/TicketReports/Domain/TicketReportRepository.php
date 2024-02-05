<?php

namespace AMovil\Reports\MantenimientoCeldas\TicketReports\Domain;

interface TicketReportRepository
{
    public function getReports();
    public function delete($ticket, $departamento);
}
