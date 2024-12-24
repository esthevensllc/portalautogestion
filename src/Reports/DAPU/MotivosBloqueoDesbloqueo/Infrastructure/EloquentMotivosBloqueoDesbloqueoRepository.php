<?php

namespace AMovil\Reports\DAPU\MotivosBloqueoDesbloqueo\Infrastructure;

use AMovil\Reports\DAPU\MotivosBloqueoDesbloqueo\Domain\MotivosBloqueoDesbloqueoRepository;
use Illuminate\Support\Facades\DB;

class EloquentMotivosBloqueoDesbloqueoRepository implements MotivosBloqueoDesbloqueoRepository
{
    public function getByImei($imei)
    {
        return DB::select("select * from DM.TER_CLARO_MOVIMIENTO
		where NUMERO_IMEI like :imei||'%'
		order by TER_FECREGISTRO asc", ["imei" => $imei]);
    }
}
