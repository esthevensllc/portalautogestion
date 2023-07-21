<?php

namespace AMovil\Reports\DAPU\SuspensionServicio\Infrastructure\Repository;

use AMovil\Reports\DAPU\SuspensionServicio\Domain\SuspensionServicioRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentSuspensionServicioRepository implements SuspensionServicioRepository
{
    public function getByMsisdn($msisdn, $dni, DateTime $fecha1, DateTime $fecha2)
    {
        $fecha_recorrido = Datetime::createFromFormat("Y-m-d", $fecha1->format('Y-m')."-01");
        $fecha_fin = Datetime::createFromFormat("Y-m-d", $fecha2->format('Y-m')."-01");

        $str_fecha1 = $fecha1->format('Y-m-d');
        $str_fecha2 = $fecha2->format('Y-m-d');

        /*$queries = [];

        while ($fecha_recorrido <= $fecha2) {
            $queries[] = "SELECT
            DISTINCT s.customer_account_sc codigo_cliente,UPPER(s.customer_FULL_name) CLIENTE, s.id_card_value NRO_DOC,
                     s.id_card_type_value DOC,
                     CASE WHEN s.subscription_status='A' THEN 'ACTIVO'
                       WHEN s.subscription_status='D' THEN 'DESACTIVO'
                       WHEN s.subscription_status='S' THEN 'SUSPENDIDO'
                       WHEN s.subscription_status='G' THEN 'PEDIDO DE GRACIA'
                       WHEN s.subscription_status='O' THEN 'BAJA OSIPTEL'
                       ELSE s.subscription_status 
                     END ESTADO,
                     s.subscription_access_number MSISDN,
                     s.subscription_start_date FECHA_ALTA,
                     s.subscription_end_date FECHA_BAJA,
                     s.agreement_mode SEGMENTO, S.AGREEMENT_REASON_STATUS_DESC AS MOTIVO_ESTADO
            FROM DWA.DW_M_SUBSCRIPTION_HIST PARTITION(P_".$fecha_recorrido->format("Ym").") s --Cambiar Periodo
            WHERE  
            s.subscription_access_number='{$msisdn}'";

            $fecha_recorrido->modify("+1 month");
        }

        return DB::select(DB::raw("SELECT * FROM (".implode(" UNION ALL ", $queries).") A ORDER BY FECHA_BAJA DESC"));*/

        

        $queries = [];

        $bindings = [];
        $counter = 1;
        while ($fecha_recorrido->format('Ym') <= $fecha_fin->format('Ym') ) {
            $bindings["p_numero1_{$counter}"] = $msisdn;
            $bindings["p_nro_doc_{$counter}"] = $dni;

            $queries[] = "SELECT
            s.customer_account_sc,
            s.customer_FULL_name,
            s.id_card_value,
            s.id_card_type_value,
            CASE WHEN s.subscription_status='A' THEN 'ACTIVO'
            WHEN s.subscription_status='D' THEN 'DESACTIVO'
            WHEN s.subscription_status='S' THEN 'SUSPENDIDO'
            ELSE s.subscription_status
            END subscription_status,
            s.subscription_access_number,
            s.subscription_start_date,
            s.subscription_end_date,
            s.agreement_mode,
            S.AGREEMENT_REASON_STATUS_DESC
            FROM DWA.DW_M_SUBSCRIPTION_HIST PARTITION(P_".$fecha_recorrido->format("Ym").") s --Cambiar Periodo
            WHERE  s.subscription_access_number='51'||:p_numero1_{$counter}
            AND s.id_card_value = :p_nro_doc_{$counter}
            AND s.DWH_CREATION_DATE >= TO_DATE('{$str_fecha1}', 'YYYY-MM-DD')
            AND s.DWH_CREATION_DATE < TO_DATE('{$str_fecha2}', 'YYYY-MM-DD')+1";

            $fecha_recorrido->modify("+1 month");
            $counter++;
        }

        $bindings['p_numero2'] = $msisdn;

        return DB::select(DB::raw("SELECT X.codigo_cliente,X.CLIENTE,X.NRO_DOC,X.DOC,X.MSISDN,X.ESTADO,Y.Fecha_Suspension,X.FECHA_ALTA,X.FECHA_BAJA,X.SEGMENTO,X.MOTIVO_ESTADO
        FROM (
            SELECT
            s.customer_account_sc as codigo_cliente,
            UPPER(s.customer_FULL_name) CLIENTE,
            s.id_card_value NRO_DOC,
            s.id_card_type_value DOC,
            CASE WHEN s.subscription_status='A' THEN 'ACTIVO'
            WHEN s.subscription_status='D' THEN 'DESACTIVO'
            WHEN s.subscription_status='S' THEN 'SUSPENDIDO'
            ELSE s.subscription_status
            END ESTADO,
            SUBSTR(s.subscription_access_number,3,11) MSISDN,
            s.subscription_start_date FECHA_ALTA,
            s.subscription_end_date FECHA_BAJA,
            s.agreement_mode SEGMENTO,S.AGREEMENT_REASON_STATUS_DESC AS MOTIVO_ESTADO
            FROM (".implode(" UNION ALL ", $queries).") s
            GROUP BY s.customer_account_sc,s.customer_FULL_name,s.id_card_value,s.id_card_type_value,s.subscription_status,s.subscription_access_number,
            s.subscription_start_date,s.subscription_end_date,s.agreement_mode,S.AGREEMENT_REASON_STATUS_DESC
            ) X
        LEFT JOIN  
            (
                select /*+PARALLEL(10)*/a.create_date as Fecha_suspension,a.phone as MSISDN,a.s_first_name||' '||a.s_last_name as NOMBRE_CLIENTE,a.s_reason_2,a.s_reason_3  
                from dws.sa_table_interact a
                where phone like '%'|| :p_numero2 ||'%' --número sin 51
                and (s_reason_3 like '%SUSPENSIÓN%' or s_reason_3 like '%SUSPENSION%' or s_reason_3 like '%SUPENSIÓN%')    
                --and to_number(to_char(create_date,'yyyymmdd'))>=20220821
                --and to_number(to_char(create_date,'yyyymmdd'))<=20221115
                and create_date >= TO_DATE('{$str_fecha1}', 'YYYY-MM-DD')
                and create_date < TO_DATE('{$str_fecha2}', 'YYYY-MM-DD')+1
            ) Y  ON  X.MSISDN=Y.MSISDN
        GROUP BY X.codigo_cliente,X.CLIENTE,X.NRO_DOC,X.DOC,X.MSISDN,X.ESTADO,Y.Fecha_Suspension,X.FECHA_ALTA,X.FECHA_BAJA,X.SEGMENTO,X.MOTIVO_ESTADO
        ORDER BY X.FECHA_BAJA DESC"), $bindings);
    }
}
