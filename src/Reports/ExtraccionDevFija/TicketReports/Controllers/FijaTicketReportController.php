<?php

namespace AMovil\Reports\ExtraccionDevFija\TicketReports\Controllers;

use AMovil\Reports\ExtraccionDevFija\TicketReports\Services\DeleteFijaTicket;
use AMovil\Reports\ExtraccionDevFija\TicketReports\Services\FijaTicketReportFinder;
use Illuminate\Http\Request;

class FijaTicketReportController
{
    private $finder;
    private $deleter;

    public function __construct(FijaTicketReportFinder $finder, DeleteFijaTicket $deleter)
    {
        $this->finder = $finder;
        $this->deleter = $deleter;
    }

    public function view()
    {
        $config = [
            "title" => "FIJA REGISTROS DE TICKET",
            "url" => url("extraccion-dev-fija/tickets/search"),
        ];
        return view("extraccion_dev_fija.ticket_reports", compact("config"));
    }

    public function search()
    {
        $response = $this->finder->getByCriteria([]);
        return response()->json($response);
    }

    public function deleteView()
    {
        $tickets = $this->finder->getByCriteria([])["data"];
        $config = [
            "title" => "ELIMINAR TICKET",
            "api" => url("extraccion-dev-fija/tickets/eliminar"),
            "tickets" => $tickets
        ];
        return view("extraccion_dev_fija.delete_ticket", compact("config"));
    }

    public function deleteTicket(Request $request)
    {
        $this->deleter->__invoke($request->input("ticket"));
    }
}
