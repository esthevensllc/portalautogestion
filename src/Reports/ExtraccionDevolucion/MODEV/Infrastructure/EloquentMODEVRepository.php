<?php

namespace AMovil\Reports\ExtraccionDevolucion\MODEV\Infrastructure;

use AMovil\Reports\ExtraccionDevolucion\MODEV\Domain\MODEVRepository;
use Illuminate\Support\Facades\DB;

class EloquentMODEVRepository implements MODEVRepository
{
    public function getReporteByTicketAndDepartamento($ticket, $departamento)
    {
        return DB::connection("oracle_reptdm")
        ->select(DB::raw("select aa.ticket,
            aa.NRO_DOCUMENTO,
            aa.ID_CLIENTE,
            aa.MSISDN,
            'Comunicaciones Personales(PCS)' SERVICIO_AFECTADO,
            aa.MODO_CONTRATACION,
        aa.CARGO_LINEA_IGV monto_plan,
            bb.diferencia tiempo_averia,
            aa.MONTO_DEVOLVER_IGV,
            'SOLES' unidad,
            case when aa.MSISDN_DEVOLVER is not null then aa.FECHA_DEVOLUCION 
                when aa.MSISDN_DEVOLVER is null then NULL END fecha_dev_fecha_comun,
            case when aa.MSISDN_DEVOLVER is not null then aa.FACTURA_APLICADA END FACTURA_APLICADA,
            case when aa.MSISDN_DEVOLVER is not null then 'ACTIVO' 
                WHEN aa.MSISDN_DEVOLVER is null then 'INACTIVO' END ESTADO,
            case when aa.MSISDN_DEVOLVER is null then aa.FECHA_BAJA 
                when aa.MSISDN_DEVOLVER is not null then NULL END fecha_baja,
            case when aa.MSISDN_DEVOLVER is null then aa.CUSTOMER_FULL_NAME 
                END NOMBRE_RAZON_SOCIAL,
            case when aa.MSISDN_DEVOLVER is null AND aa.MODALIDAD_DEV like '%WEB%' THEN 'CAC' 
            WHEN aa.MSISDN_DEVOLVER is null AND aa.MODALIDAD_DEV NOT LIKE '%WEB%' THEN '' END LUGAR_DONDE_COBRAR,
            case when aa.MSISDN_DEVOLVER is null AND aa.MODALIDAD_DEV like '%WEB%' THEN 'documento' 
            WHEN aa.MSISDN_DEVOLVER is null AND aa.MODALIDAD_DEV NOT LIKE '%WEB%' THEN '' END REQUISITO_COBRO,
            case when aa.MSISDN_DEVOLVER is null AND aa.MODALIDAD_DEV like '%WEB%' THEN 'SI' 
            WHEN aa.MSISDN_DEVOLVER is null AND aa.MODALIDAD_DEV NOT LIKE '%WEB%' THEN 'NO' END COMUNICACION, 
            case when aa.MSISDN_DEVOLVER is null AND aa.MODALIDAD_DEV like '%WEB%' THEN 'WEB' 
            WHEN aa.MSISDN_DEVOLVER is null AND aa.MODALIDAD_DEV NOT LIKE '%WEB%' then 'NO_APLICA' END MEDIO_COMUNICACION,
            'NO_APLICA' NO_CORRESPONDE,
            CASE WHEN MODO_CONTRATACION_DEV='POSTPAGO' THEN 'DEVOLUCION APLICADA-POSTPAGO' 
            WHEN MODO_CONTRATACION_DEV='PREPAGO' THEN 'DEVOLUCION APLICADA-PREPAGO' 
            WHEN aa.MODALIDAD_DEV like '%WEB%' THEN 'DEVOLUCION WEB' END COMENTARIOS,
            null liberado
        from USRAES.BASE_PREV_BASEDEV aa
        left join (
            SELECT ticket,corte_fecha_ini,corte_fecha_fin,(corte_fecha_fin-corte_fecha_ini)*24*60 diferencia
            FROM usraes.base_prev_basedev_input where ticket=:p1 and departamento= :p2  
            ) bb 
        on aa.ticket=bb.ticket and aa.FECHA_CORTE=bb.corte_fecha_ini
        where aa.ticket= :p3 and departamento= :p4"), [
            "p1" => $ticket, "p2" => $departamento,
            "p3" => $ticket, "p4" => $departamento
        ]);
    }
}
