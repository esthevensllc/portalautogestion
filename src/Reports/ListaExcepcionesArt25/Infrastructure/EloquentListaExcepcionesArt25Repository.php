<?php

namespace AMovil\Reports\ListaExcepcionesArt25\Infrastructure;

use AMovil\Reports\ListaExcepcionesArt25\Domain\ListaExcepcionesArt25Repository;
use Illuminate\Support\Facades\DB;

class EloquentListaExcepcionesArt25Repository implements ListaExcepcionesArt25Repository
{
    public function findAll()
    {
        return DB::select("select * from DWS.SA_RENTESEG_LISTA_EXCEPCION");
    }

    public function findLast()
    {
        return DB::select("select * from DWS.SA_RENTESEG_LISTA_EXCEPCION
        where trunc(TRANSACT_DATE, 'dd') = trunc((select max(TRANSACT_DATE) from DWS.SA_RENTESEG_LISTA_EXCEPCION), 'dd')
        ");
    }

    public function getByImei($imei)
    {
        return DB::select("SELECT TRANSACT_DATE, SUBSTR(IMEI,0,14) IMEI, OPERACION, MSISDN, IMSI FROM DWS.SA_RENTESEG_LISTA_EXCEPCION WHERE SUBSTR(IMEI,0,14) = :imei", ["imei" => $imei]);
    }

}
