<?php

namespace AMovil\Reports\DAPU\MotivosBloqueoDesbloqueo\Infrastructure;

use AMovil\Reports\DAPU\MotivosBloqueoDesbloqueo\Domain\MotivosBloqueoDesbloqueoRepository;
use Illuminate\Support\Facades\DB;

class EloquentMotivosBloqueoDesbloqueoRepository implements MotivosBloqueoDesbloqueoRepository
{
    public function getByLinea($linea)
    {
        return $this->getResult("C.TER_NUMEROLINEA", $linea);
    }

    public function getByImei($imei)
    {
        return $this->getResult("C.NUMERO_IMEI", $imei);
    }

    public function getResult($field, $value)
    {
        $query = "SELECT /*+ PARALLEL(8) */ 
        C.ID_TERMOVIMIENTO,
        C.NUMERO_IMEI IMEI,
        C.TER_NUMEROLINEA LINEA,
        C.TER_ASESOR_SERVICIO COD_ASESOR,
        C.TER_FECREGISTRO FECHA_TRANSACCION,
        C.TER_ESTADO ESTADO_TRANSACCION,
        C.TER_DES_MOTIVO MOTIVO_TRANSACCION,
        C.TER_MARCA_MODELO DETALLE_EQUIPO
        FROM DM.TER_CLARO_MOVIMIENTO C 
        WHERE {$field} = :value
        ORDER BY C.TER_FECREGISTRO";

        return DB::select($query, ["value" => $value]);
    }
}
