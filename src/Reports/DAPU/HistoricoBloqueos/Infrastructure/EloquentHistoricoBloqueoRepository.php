<?php

namespace AMovil\Reports\DAPU\HistoricoBloqueos\Infrastructure;

use AMovil\Reports\DAPU\HistoricoBloqueos\Domain\HistoricoBloqueoRepository;
use Illuminate\Support\Facades\DB;

class EloquentHistoricoBloqueoRepository implements HistoricoBloqueoRepository
{
    public function getByImei($imei)
    {
        $query = "select 
        NUMERO_IMEI IMEI,TER_FECREGISTRO FECHA_REGISTRO, TER_NUMEROLINEA LINEA, TER_ESTADO, TER_DES_MOTIVO
        from dws.sa_ter_claro_movimiento
        where numero_imei LIKE '%'|| ? ||'%'";
        return DB::select(DB::raw($query), [$imei]);
    }
}
