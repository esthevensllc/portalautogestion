<?php

namespace AMovil\Reports\ExtraccionDevolucion\CarteraGobierno\Infrastructure;

use AMovil\Reports\ExtraccionDevolucion\CarteraGobierno\Domain\CarteraGobiernoRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentCarteraGobiernoRepository implements CarteraGobiernoRepository
{
    public function saveAll($data)
    {
        $strDay = (new DateTime())->format("Y-m-d")." 00:00:00";
        DB::connection("oracle_reptdm")
        ->table("usraes.cartera_gobierno")
        ->where("dia", $strDay)
        ->delete();

        foreach ($data as $row) {
            DB::connection("oracle_reptdm")
            ->table("usraes.cartera_gobierno")
            ->insert([
                "dia" => $strDay,
                "ruc" => $row["ruc"],
                "entidad_razon_social" => $row["entidad_razon_social"],
            ]);
        }
    }
}
