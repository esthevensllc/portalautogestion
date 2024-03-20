<?php

namespace AMovil\Reports\ListaExcepciones\Infrastructure;

use AMovil\Reports\ListaExcepciones\Domain\ListaExcepcionesRepository;
use Illuminate\Support\Facades\DB;

class EloquentListaExcepcionesRepository implements ListaExcepcionesRepository
{
    public function findAll()
    {
        return DB::select("select * from TABLE_LISTA_EXCEPCIONES");
    }

    public function findLast()
    {
        return DB::select("select * from TABLE_LISTA_EXCEPCIONES
        where trunc(fecha_registro, 'dd') = trunc((select max(fecha_registro) from TABLE_LISTA_EXCEPCIONES), 'dd')
        ");
    }

    public function getByImei($imei)
    {
        return DB::select("select * from TABLE_LISTA_EXCEPCIONES where imei = :imei", ["imei" => $imei]);
    }

}
