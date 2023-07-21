<?php

namespace AMovil\Reports\ExtraccionDevolucion\MODEV\Controllers;

use AMovil\Reports\ExtraccionDevolucion\MODEV\Services\ExportMODEV;
use AMovil\Reports\ExtraccionDevolucion\TicketReports\Services\GetTicketReports;
use Illuminate\Http\Request;

class MODEVController
{
    private $export;
    private $getTicketReports;

    public function __construct(ExportMODEV $export, GetTicketReports $getTicketReports)
    {
        $this->export = $export;
        $this->getTicketReports = $getTicketReports;
    }

    public function view()
    {
        $tickets = [];
        $departamentos = [];
        $tickets = $this->getTicketReports->getTickets();
        $departamentos = $this->getTicketReports->getDepartamentos();

        $config = [
            "title" => "FORMAT MODEV",
            "url" => asset("extraccion-devolucion/modev/export"),
            "tickets" => $tickets,
            "departamentos" => $departamentos,
        ];
        return view("extraccion_devolucion.modev", compact("config"));
    }

    public function export(Request $request)
    {
        $content = $this->export->__invoke(
            $request->input("ticket"),
            $request->input("departamento")
        )->data();

        return response($content["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$content["filename"].'"'
        ]);
    }
}
