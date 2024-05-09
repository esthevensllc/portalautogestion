<?php

namespace AMovil\Reports\ListaExcepcionesEliminar\Infrastructure;

use AMovil\Reports\ListaExcepcionesEliminar\Domain\ListaExcepcionesEliminarRepository;
use Illuminate\Support\Facades\DB;

class EloquentListaExcepcionesEliminarRepository implements ListaExcepcionesEliminarRepository
{
    public function findAll()
    {
        return DB::select("select * from TABLE_LISTA_EXCEPCIONES_ELIMINAR");
    }

    public function findLast()
    {
        return DB::select("select * from TABLE_LISTA_EXCEPCIONES_ELIMINAR
        where trunc(fecha_registro, 'dd') = trunc((select max(fecha_registro) from TABLE_LISTA_EXCEPCIONES_ELIMINAR), 'dd')
        ");
    }

    public function getByImei($imei)
    {
        return DB::select("select * from TABLE_LISTA_EXCEPCIONES_ELIMINAR where imei = :imei", ["imei" => $imei]);
    }
}
