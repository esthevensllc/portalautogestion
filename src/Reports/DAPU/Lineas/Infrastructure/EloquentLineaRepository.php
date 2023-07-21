<?php

namespace AMovil\Reports\DAPU\Lineas\Infrastructure;

use AMovil\Reports\DAPU\Lineas\Domain\LineaRepository;
use Illuminate\Support\Facades\DB;

class EloquentLineaRepository implements LineaRepository
{
    public function getUsuariosByLinea($linea)
    {
        $data = DB::select(DB::raw("select
        s.subscription_access_number LINEA,
        s.CUSTOMER_FULL_NAME CLIENTE,
        s.ID_CARD_TYPE_VALUE TIPO_DOC,
        s.ID_CARD_VALUE NUM_DOC,
        s.CUSTOMER_ADDRESS DIRECCION,
        s.CUSTOMER_ACCOUNT_BILLING_DEPARTMENT DEPARTAMENTO,
        s.CUSTOMER_ACCOUNT_BILLING_PROVINCE PROVINCIA,
        s.CUSTOMER_ACCOUNT_BILLING_DISTRICT DISTRITO
        FROM DWA.DW_M_SUBSCRIPTION S 
        WHERE S.subscription_access_number = to_char(?)"), [$linea]);
        return $data;
    }
}
