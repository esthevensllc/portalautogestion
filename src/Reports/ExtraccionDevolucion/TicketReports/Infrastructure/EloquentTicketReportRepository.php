<?php

namespace AMovil\Reports\ExtraccionDevolucion\TicketReports\Infrastructure;

use AMovil\Reports\ExtraccionDevolucion\TicketReports\Domain\TicketReportRepository;
use Illuminate\Support\Facades\DB;

class EloquentTicketReportRepository implements TicketReportRepository
{
    private $connection = "oracle_reptdm";

    public function deleteBy($ticket, $departamento)
    {
        DB::connection($this->connection)
        ->statement("DECLARE
            V_TICKET VARCHAR2(100) := :p_ticket;
            V_DEPARTAMENTO VARCHAR2(100) := :p_departamento;
        BEGIN
        DELETE FROM USRAES.BASE_PREV_BASEDEV
        WHERE TICKET=V_TICKET AND DEPARTAMENTO=V_DEPARTAMENTO AND FLAG_CONFIRMACION=0;
        commit;
        
        delete from USRAES.BASE_PREV_BASEDEV_HIST
        WHERE TICKET=V_TICKET AND DEPARTAMENTO=V_DEPARTAMENTO AND FLAG_CONFIRMACION=0;
        commit;

        delete from USRAES.BASE_PREV_BASEDEV_INPUT
        WHERE TICKET=V_TICKET AND DEPARTAMENTO=V_DEPARTAMENTO;
        COMMIT;

        delete from USRAES.BASE_PREV_BASEDEV_INPUT_CELDA
        WHERE TICKET=V_TICKET AND DEPARTAMENTO=V_DEPARTAMENTO;
        COMMIT;

        END;", ["p_ticket" => $ticket, "p_departamento" => $departamento]);
    }

    public function reportIsConfirmed($ticket, $departamento)
    {
        $hist = DB::connection($this->connection)
        ->table("USRAES.BASE_PREV_BASEDEV_HIST")
        ->where("ticket", $ticket)
        ->where("departamento", $departamento)
        ->first();
        
        if($hist === null){
            return false;
        }
        return ((int) $hist->flag_confirmacion) === 1;
    }

    public function confirmBy($ticket, $departamento)
    {
        DB::connection($this->connection)
        ->statement("DECLARE
            V_TICKET VARCHAR2(100) := :p_ticket;
            V_DEPARTAMENTO VARCHAR2(100) := :p_departamento;
        BEGIN
        UPDATE USRAES.BASE_PREV_BASEDEV SET
        FLAG_CONFIRMACION = 1
        WHERE TICKET=V_TICKET AND DEPARTAMENTO=V_DEPARTAMENTO AND FLAG_CONFIRMACION=0;
        commit;
        
        UPDATE USRAES.BASE_PREV_BASEDEV_HIST SET
        FLAG_CONFIRMACION = 1
        WHERE TICKET=V_TICKET AND DEPARTAMENTO=V_DEPARTAMENTO AND FLAG_CONFIRMACION=0;
        commit;
        END;", ["p_ticket" => $ticket, "p_departamento" => $departamento]);
    }

    public function findInputFor($ticket, $departamento)
    {
        $departamento = str_replace(["á","é","í","ó","ú","Á","É","Í","Ó","Ú"], ["a","e","i","o","u","A","E","I","O","U"], $departamento);
        $celdas = DB::connection($this->connection)
        ->table("USRAES.BASE_PREV_BASEDEV_INPUT_CELDA")
        ->select("celda")
        ->where("ticket", $ticket)
        ->where("departamento", $departamento)
        ->get();

        $celdasArray = [];
        foreach($celdas as $row){
            $celdasArray[] = $row->celda;
        }

        $result = DB::connection($this->connection)
        ->table("USRAES.BASE_PREV_BASEDEV_INPUT")
        ->where("ticket", $ticket)
        ->where("departamento", $departamento)
        ->first();
        if($result !== null){
            $result->celda = implode(",", $celdasArray);
        }
        return $result;
    }

    public function getTickets()
    {
        // return json_decode(json_encode([["ticket" => 1], ["ticket" => 2]]));
        $tickets = DB::connection($this->connection)
        ->table("USRAES.BASE_PREV_BASEDEV_HIST")
        ->select("ticket")
        ->groupBy("ticket")
        ->get();
        return $tickets;
    }

    public function getDepartamentos()
    {
        // return json_decode(json_encode([
        //     ["departamento" => "D1", "ticket" => 1],
        //     ["departamento" => "D2", "ticket" => 2]
        // ]));

        $departamentos = DB::connection($this->connection)
        ->table("USRAES.BASE_PREV_BASEDEV_HIST")
        ->select("ticket", "departamento")
        ->get();
        return $departamentos;
    }

    public function findByTicketAndDepartamento($ticket, $departamento)
    {
        return DB::connection($this->connection)
        ->table("USRAES.BASE_PREV_BASEDEV_HIST")
        ->where("ticket", $ticket)
        ->where("departamento", $departamento)
        ->first();
    }

    public function findByTicket($ticket)
    {
        return DB::connection($this->connection)
        ->table("USRAES.BASE_PREV_BASEDEV_HIST")
        ->where("ticket", $ticket)
        ->first();
    }
}
