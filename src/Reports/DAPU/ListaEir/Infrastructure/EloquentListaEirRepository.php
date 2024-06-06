<?php

namespace AMovil\Reports\DAPU\ListaEir\Infrastructure;

use AMovil\Reports\DAPU\ListaEir\Domain\ListaEirRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentListaEirRepository implements ListaEirRepository
{
    public function getByImei($imei)
    {
        $now = (new DateTime())->format('Ymd');
        $query = "SELECT to_char(transact_date,'dd/mm/yyyy hh24:mi:ss') transact_date,
        SUBSTR(imei,0,14) imei,
		to_char(reg_time,'dd/mm/yyyy hh12:mi:ss AM') FECHA_INGRESO_BLO
		from DWS.SA_EIR partition (P_{$now})
		where SUBSTR(imei,0,14) = :imei";
        return DB::select($query, ["imei" => $imei]);
    }
}
