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
        a.FECHA_BAJA,
        a.NOMCLI,
        '' LUGAR_DONDE_COBRAR,
        '' REQUISITOS_PARA_EL_COBRO,
        '' COMUNICACION,
        '' MEDIO_DE_COMUNICACION,
        'NO_APLICA' MOTIVO_SOLO_CUANDO_NO_CORRESPONDE,
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
