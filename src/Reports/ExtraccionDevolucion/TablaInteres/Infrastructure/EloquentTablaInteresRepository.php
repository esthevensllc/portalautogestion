<?php

namespace AMovil\Reports\ExtraccionDevolucion\TablaInteres\Infrastructure;

use AMovil\Reports\ExtraccionDevolucion\TablaInteres\Domain\TablaInteresRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentTablaInteresRepository implements TablaInteresRepository
{
    private $connection = "oracle_reptdm";

    public function insert(DateTime $fecha, $tasa, $factorDiario, $factorAcumulado)
    {
        DB::connection($this->connection)
        ->table("USRAES.DWH_TASAINTERES_SBS")
        ->insert([
            "fecha" => $fecha->format("Y-m-d")." 00:00:00",
            "tasa" => $tasa,
            "factordiario" => $factorDiario,
            "factoracumulado" => $factorAcumulado,
            "fecha_carga" => (new DateTime())->format("Ymd"),
        ]);
    }

    public function getMaxFechaInteres()
    {
        $row = DB::connection($this->connection)
        ->table("USRAES.DWH_TASAINTERES_SBS")
        ->selectRaw("max(fecha) as fecha")
        ->first();
        return $row->fecha;
    }

    public function getFactorAcumuladoByFecha(int $limit = 10)
    {
        return DB::connection($this->connection)
        ->select(DB::raw("select fecha,FACTORACUMULADO from USRAES.DWH_TASAINTERES_SBS
        group by fecha,FACTORACUMULADO order by fecha desc fetch first {$limit} rows only"));
    }

    public function existsIn(DateTime $fecha): bool {
        return DB::connection($this->connection)
        ->table("USRAES.DWH_TASAINTERES_SBS")
        ->where("fecha", $fecha->format("Y-m-d")." 00:00:00")
        ->exists();
    }
}
