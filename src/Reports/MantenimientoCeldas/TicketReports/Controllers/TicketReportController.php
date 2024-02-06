<?php

namespace AMovil\Reports\MantenimientoCeldas\TicketReports\Controllers;

use AMovil\Reports\MantenimientoCeldas\TicketReports\Services\DeleteTicketReport;
use AMovil\Reports\MantenimientoCeldas\TicketReports\Services\TicketReportFinder;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;

class TicketReportController
{
    private $finder;
    private $deleter;

    public function __construct(TicketReportFinder $finder, DeleteTicketReport $deleter)
    {
        $this->finder = $finder;
        $this->deleter = $deleter;
    }

    public function view()
    {
        $config = [
            "title" => "REGISTROS DE MANTENIMIENTO",
            "url" => asset("mantenimiento-celdas/tickets/search")
        ];
        return view("mantenimiento_celdas.ticket_reports", compact("config"));
    }

    public function search()
    {
        return response()->json([
            "data" => $this->finder->__invoke(),
        ]);
    }

    public function deleteView()
    {
        $config = [
            "title" => "ELIMINAR MANTENIMIENTO",
            "api" => url("mantenimiento-celdas/eliminar-ticket"),
            "tickets" => $this->finder->__invoke(),
        ];
        return view("mantenimiento_celdas.delete_ticket_report", compact("config"));
    }

    public function delete(Request $request)
    {
        $this->deleter->__invoke($request->input("ticket"), $request->input("departamento"));
        return response()->json([]);
    }
}
