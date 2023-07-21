<?php

namespace AMovil\Reports\DAPU\Adquisiciones\Infrastructure\Repository;

use AMovil\Reports\DAPU\Adquisiciones\Domain\AdquisicionRepository;
use Illuminate\Support\Facades\DB;

class EloquentAdquisicionRepository implements AdquisicionRepository
{
    public function getByImei(string $imei)
    {
        $sql = "SELECT XX.FECHA_ADQUISICION,XX.IMEI,XX.MSISDN,XX.TIPO_DOC,XX.NUM_DOC,XX.CLIENTE,XX.SEGMENTO,XX.DESCRIP_RAZON_VENTA,XX.PLAN_ADQUIRIDO,XX.MARCA,XX.MODELO  
                    FROM
                    (
                        SELECT X.FECHA_ADQUISICION,X.IMEI,X.MSISDN,X.TIPO_DOC,X.NUM_DOC,X.CLIENTE,X.SEGMENTO,X.DESCRIP_RAZON_VENTA,
                                    X.PLAN_ADQUIRIDO,X.MARCA,X.MODELO,
                                row_number() OVER (PARTITION BY X.msisdn ORDER BY X.plan_adquirido) AS NROW  
                                FROM
                            (
                                SELECT /*+PARALLEL(10)*/ sale_date AS FECHA_ADQUISICION,serial_number AS IMEI,product_number AS MSISDN,customer_document_type_desc AS TIPO_DOC,id_card_value AS NUM_DOC,
                                customer_given_name||' '||customer_name CLIENTE,
                                sales_type_desc AS SEGMENTO,sales_reason_desc AS DESCRIP_RAZON_VENTA,prod_offer_plan_desc AS PLAN_ADQUIRIDO,
                                prod_offer_item_brand_desc AS MARCA,prod_offer_item_model_desc AS MODELO
                                FROM dwa.dw_t_customer_sale  WHERE
                                serial_number LIKE '%'||? 
                                UNION ALL
                                --SI NO SE ENCUENTRA INFORMACIÓN, VALIDAR LA SIGUIENTE CONSULTA(VALIDACIÓN EN LA TABLE DW_T_SALES:
                                SELECT /*+PARALLEL(10)*/ sale_date AS FECHA_ADQUISICION,serial_number AS IMEI,product_number AS MSISDN,customer_document_type_desc AS TIPO_DOC,id_card_value AS NUM_DOC,
                                customer_given_name||' '||customer_name AS CLIENTE,
                                sales_type_desc AS SEGMENTO,sales_reason_desc AS DESCRIP_RAZON_VENTA,prod_offer_plan_desc AS PLAN_ADQUIRIDO,
                                prod_offer_item_brand_desc AS MARCA,prod_offer_item_model_desc AS MODELO  
                                FROM dwa.dw_t_sales  
                                WHERE serial_number LIKE '%'||?
                            ) X
                    ) XX WHERE XX.NROW=1";

        $data = DB::select(DB::raw($sql), [$imei,$imei]);

        if (count($data) === 0) {
            $sql = "SELECT /*+PARALLEL(10)*/ sale_date AS FECHA_ADQUISICION,serial_number AS IMEI,product_number AS MSISDN,customer_document_type_desc AS TIPO_DOC,id_card_value AS NUM_DOC,
                customer_given_name||' '||customer_name AS CLIENTE,
                sales_type_desc AS SEGMENTO,sales_reason_desc AS DESCRIP_RAZON_VENTA,prod_offer_plan_desc AS PLAN_ADQUIRIDO,
                prod_offer_item_brand_desc AS MARCA,prod_offer_item_model_desc AS MODELO  
                FROM dwa.dw_t_sales  
                WHERE serial_number LIKE '%'||?";
            $data = DB::select(DB::raw($sql), [$imei]);
        }
        return $data;
    }
}
