<?php

namespace AMovil\Reports\ExtraccionDevolucion\TicketReports\Controllers;

use AMovil\Reports\ExtraccionDevolucion\TicketReports\Services\ConfirmTicketReport;
use AMovil\Reports\ExtraccionDevolucion\TicketReports\Services\DeleteTicketReport;
use AMovil\Reports\ExtraccionDevolucion\TicketReports\Services\FindTicketReportInput;
use AMovil\Reports\ExtraccionDevolucion\TicketReports\Services\GetTicketReports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketReportsController
{
    private $deleteReport;
    private $confirmReport;
    private $findReportInput;
    private $getTicketReports;

    public function __construct(DeleteTicketReport $deleteReport, ConfirmTicketReport $confirmReport, FindTicketReportInput $findReportInput, GetTicketReports $getTicketReports)
    {
        $this->deleteReport = $deleteReport;
        $this->confirmReport = $confirmReport;
        $this->findReportInput = $findReportInput;
        $this->getTicketReports = $getTicketReports;
    }

    public function view()
    {
        $config = [
            "title" => "REGISTROS DE TICKET",
            "url" => asset("extraccion-devolucion/tickets/search")
        ];
        return view("extraccion_devolucion.ticket_reports", compact("config"));
    }

    public function search()
    {
        $data = DB::connection("oracle_reptdm")->table("USRAES.BASE_PREV_BASEDEV_HIST")->get();
        return response()->json([
            "data" => $data
        ]);
    }

    public function deleteView()
    {
        $tickets = $this->getTicketReports->getTickets();
        $departamentos = $this->getTicketReports->getDepartamentos();

        $config = [
            "title" => "ELIMINAR TICKET",
            "api" => asset("extraccion-devolucion/eliminar-ticket"),
            "tickets" => $tickets,
            "departamentos" => $departamentos,
        ];
        return view("extraccion_devolucion.del_ticket_reports", compact("config"));
    }

    public function delete(Request $request)
    {
        $this->deleteReport->__invoke(
            $request->input("ticket"),
            $request->input("departamento")
        );
        return response()->json([]);
    }
    
    public function confirm(Request $request)
    {
        $this->confirmReport->__invoke(
            $request->input("ticket"),
            $request->input("departamento")
        );
        return response()->json([]);
    }
    
    public function findInput(Request $request)
    {
        $resp = $this->findReportInput->__invoke(
            $request->input("ticket"),
            $request->input("departamento")
        )->data();
        return response()->json(["data" => $resp]);
    }
}
