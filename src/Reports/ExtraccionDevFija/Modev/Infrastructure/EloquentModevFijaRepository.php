<?php

namespace AMovil\Reports\ExtraccionDevFija\Modev\Infrastructure;

use AMovil\Reports\ExtraccionDevFija\Modev\Domain\ModevFijaRepository;
use Illuminate\Support\Facades\DB;

class EloquentModevFijaRepository implements ModevFijaRepository
{
    public function getReporteModev(array $tickets){
        $ticketBinds = $this->getQueryBinds($tickets);

        $query = "SELECT
        a.TICKET,
        a.TIPDOC as tipo_documento,
        a.NRO_DOC,
        a.CODCLI as ID_CLIENTE,
        a.NUMERO AS MSISDN,
        servicio.SERVICIO_AFECTADO SERVICIO_AFECTADO,
        'POSTPAGO' MODO_CONTRATACION,
        a.CR_NETOCIGV,
        round((min.fecha_fin - min.fecha_ini)*24*60) MINUTOS,
        a.MTO_DEV_FACTURACION,
        a.MONEDA_DEVOL,
        a.FECHA_DEVOLUCION,
        CASE WHEN a.FACTURA_APLICADA LIKE '=%' THEN NULL ELSE a.FACTURA_APLICADA END FACTURA_APLICADA,
        CASE WHEN a.ESTADO_CONTRATO IN ('A','G') THEN 'ACTIVO' ELSE 'INACTIVO' END AS ESTADO,
        a.FECHA_BAJA,
        a.NOMCLI,
        '' LUGAR_DONDE_COBRAR,
        '' REQUISITOS_PARA_EL_COBRO,
        '' COMUNICACION,
        '' MEDIO_DE_COMUNICACION,
        'NO_APLICA' MOTIVO_SOLO_CUANDO_NO_CORRESPONDE,
        a.COMENTARIOS,
        '' LIBERADO
        from
        USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST a
        left join USRAES.DWH_DEVOLUCION_MASIV_DETALLE_TOTAL servicio
        on servicio.ticket = a.ticket
        left join (
            select ticket, fecha_fin, fecha_ini from usraes.input_devo_fija
            group by ticket, fecha_fin, fecha_ini
        ) min
        on min.ticket = a.ticket
        join 
        (
            select * from usraes.noc_informe_de_fallas_fija
            where tipo_reporte=2
        ) niff
        on a.ticket=niff.ticket
        where a.TICKET IN ({$ticketBinds['str_binds']})
        UNION ALL
        SELECT
        a.TICKET,
        a.TIPDOC as tipo_documento,
        a.NRO_DOC,
        a.CODCLI as ID_CLIENTE,
        a.NUMERO AS MSISDN,
        servicio.SERVICIO_AFECTADO SERVICIO_AFECTADO,
        'POSTPAGO' MODO_CONTRATACION,
        a.CR_NETOCIGV,
        round((min.fecha_fin - min.fecha_ini)*24*60) MINUTOS,
        a.MTO_DEV_FACTURACION,
        a.MONEDA_DEVOL,
        a.FECHA_DEVOLUCION,
        CASE WHEN a.FACTURA_APLICADA LIKE '=%' THEN NULL ELSE a.FACTURA_APLICADA END FACTURA_APLICADA,
        'ACTIVO' AS ESTADO,
        --CASE WHEN a.ESTADO_CONTRATO IN ('A','G') THEN 'ACTIVO' ELSE 'INACTIVO' END AS ESTADO,
        a.FECHA_BAJA,
        a.NOMCLI,
        '' LUGAR_DONDE_COBRAR,
        '' REQUISITOS_PARA_EL_COBRO,
        '' COMUNICACION,
        '' MEDIO_DE_COMUNICACION,
        'NO_APLICA' MOTIVO_SOLO_CUANDO_NO_CORRESPONDE,
        -- a.COMENTARIOS,
        'DEVOLUCION APLICADA-POSTPAGO' COMENTARIOS,
        '' LIBERADO
        from
        USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST a
        left join USRAES.DWH_DEVOLUCION_MASIV_DETALLE_TOTAL servicio
        on servicio.ticket = a.ticket
        left join (
            select ticket, fecha_fin, fecha_ini from usraes.input_devo_fija
            group by ticket, fecha_fin, fecha_ini
        ) min
        on min.ticket = a.ticket
        join 
        (
            select * from usraes.noc_informe_de_fallas_fija
            where tipo_reporte=1
        ) niff
        on a.ticket=niff.ticket
        where a.TICKET IN ({$ticketBinds['str_binds']}) AND ESTADO_CONTRATO='A'
        and (CASE FUENTE
        WHEN 'BSCS' THEN (CASE WHEN ESTADO_CONTRATO != 'D' THEN 1 ELSE 0 END)
        WHEN 'SGA' THEN (CASE WHEN CICFAC_DEVOL IS NOT NULL AND FCHFIN_INST IS NULL THEN 1 ELSE 0 END)
        ELSE 0 END
        ) = 1
        AND to_number(CICFAC_DEVOL) not in (29, 3, 6, 11, 21, 37, 114, 39, 26, 36, 12, 20)";

        return DB::select(DB::raw($query), $ticketBinds['values']);
    }

    public function getReporteLogPrepago(array $tickets){
        $ticketBinds = $this->getQueryBinds($tickets);

        $query = "SELECT
        TICKET,
        NUMERO,
        ROUND(ROUND(MONTO_PRINCIPAL * 1.18, 2) + INTERES, 2)*100 CANTIDAD,
        FECHA_DEVOLUCION RECHARGE_DATE,
        ROUND(MTO_DEV_FACTURACION*100,2) RECARGA,
        MSISDN_DEVOLVER,
        '92000559;Prepago_Devolucion_Interrupciones_Osiptel' USAGE_STR3
        from USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
        WHERE COMENTARIOS like '%PREPAGO%'
        AND TICKET in ({$ticketBinds['str_binds']})";

        return DB::select(DB::raw($query), $ticketBinds['values']);
    }

    public function getReporteModevMantenimiento(array $tickets){
        $ticketBinds = $this->getQueryBinds($tickets);

        $query = "SELECT
        a.TICKET,
        a.TIPDOC as tipo_documento,
        a.NRO_DOC,
        a.CODCLI as ID_CLIENTE,
        a.NUMERO AS MSISDN,
        servicio.SERVICIO_AFECTADO SERVICIO_AFECTADO,
        'POSTPAGO' MODO_CONTRATACION,
        a.CR_NETOCIGV,
        round((min.fecha_fin - min.fecha_ini)*24*60) MINUTOS,
        a.MTO_DEV_FACTURACION,
        a.MONEDA_DEVOL,
        a.FECHA_DEVOLUCION,
        CASE WHEN a.FACTURA_APLICADA LIKE '=%' THEN NULL ELSE a.FACTURA_APLICADA END FACTURA_APLICADA,
        CASE WHEN a.ESTADO_CONTRATO IN ('A','G') THEN 'ACTIVO' ELSE 'INACTIVO' END AS ESTADO,
        a.FECHA_BAJA,
        a.NOMCLI,
        '' LUGAR_DONDE_COBRAR,
        '' REQUISITOS_PARA_EL_COBRO,
        '' COMUNICACION,
        '' MEDIO_DE_COMUNICACION,
        'NO_APLICA' MOTIVO_SOLO_CUANDO_NO_CORRESPONDE,
        a.COMENTARIOS,
        '' LIBERADO
        from
        USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST a
        left join USRAES.DWH_DEVOLUCION_MASIV_DETALLE_TOTAL servicio
        on servicio.ticket = a.ticket
        left join (
            select ticket, fecha_fin, fecha_ini from usraes.input_devo_fija
            group by ticket, fecha_fin, fecha_ini
        ) min
        on min.ticket = a.ticket
        where a.TICKET IN ({$ticketBinds['str_binds']})";

        return DB::select(DB::raw($query), $ticketBinds['values']);
    }

    private function getQueryBinds(array $tickets){
        $str_binds = [];
        $bind_values = [];
        foreach($tickets as $index => $value){
            $str_binds[] = ":p_ticket_{$index}";
            $bind_values["p_ticket_{$index}"] = $value;
        }
        $str_binds = implode(", ", $str_binds);
        return [
            "str_binds" => $str_binds,
            "values" => $bind_values
        ];
    }
}
