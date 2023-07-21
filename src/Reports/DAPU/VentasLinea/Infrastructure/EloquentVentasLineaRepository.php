<?php

namespace AMovil\Reports\DAPU\VentasLinea\Infrastructure;

use AMovil\Reports\DAPU\VentasLinea\Domain\VentasLineaRepository;
use Illuminate\Support\Facades\DB;

class EloquentVentasLineaRepository implements VentasLineaRepository
{
    public function getByDni_Fono_Periodo($dni, $fono, $periodo)
    {
        $query = "select sale_date fecha_venta,
        SERIAL_NUMBER imei,
        id_card_value as N_DOC,
        customer_name as CLIENTE,
        PRODUCT_NUMBER fono,
        PROD_OFFER_ITEM_NAME producto,
        PROD_OFFER_PLAN_DESC plan_producto,
        SALES_PERSON_NAME,
        PDV_DESC,
        PDV_RAZON_SOCIAL_DESC,
        PDV_CHANNEL_DESC,
        PDV_PVU_CHANNEL_DESC canal,
        sales_reason_desc
        from dwa.dw_t_sales 
        where product_number='51'||? and id_card_value=?";

        $values = [$fono, $dni];

        if($periodo !== null){
            $query .= " and to_char(sale_date, 'yyyymm') = ?";
            $values[] = $periodo;
        }

        return DB::select(DB::raw($query), $values);
    }
}
