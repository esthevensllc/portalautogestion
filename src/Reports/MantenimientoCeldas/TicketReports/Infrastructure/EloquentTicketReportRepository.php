<?php

namespace AMovil\Reports\MantenimientoCeldas\TicketReports\Infrastructure;

use AMovil\Reports\MantenimientoCeldas\TicketReports\Domain\TicketReportRepository;
use Illuminate\Support\Facades\DB;

class EloquentTicketReportRepository implements TicketReportRepository
{
    public function getReports()
    {
        return DB::table("USRAES.M_REPORTES_HIST")->get();
    }

    public function delete($ticket, $departamento)
    {
        DB::table("USRAES.M_REPORTES_ALL")
        ->where("ticket", $ticket)
        ->where("departamento", $departamento)
        ->delete();
        
        DB::table("USRAES.M_REPORTES_HIST")
        ->where("ticket", $ticket)
        ->where("departamento", $departamento)
        ->delete();
    }
}
