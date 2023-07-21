<?php

namespace AMovil\Reports\DAPU\FONO\Infrastructure;

use AMovil\Reports\DAPU\FONO\Domain\FonoRepository;
use Illuminate\Support\Facades\DB;

class EloquentFonoRepository implements FonoRepository
{
    public function getByFono($fono)
    {
        $data = DB::select(DB::raw("select
        s.subscription_access_number fono,
        s.CUSTOMER_FULL_NAME nombre,
        s.SUBSCRIPTION_START_DATE fecha_alta,
        s.SUBSCRIPTION_END_DATE fecha_baja,
        s.ID_CARD_VALUE numero_doc,
        s.ID_CARD_TYPE_VALUE tipo_doc 
        from DWA.DW_M_SUBSCRIPTION_HIST  PARTITION(P_202301) s
        where s.subscription_access_number='51'||?"), [$fono]);
        return $data;
    }
}
