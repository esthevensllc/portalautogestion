<?php

namespace AMovil\Reports\DetallePlanes\ConsolidadoMinutos\Infrastructure;

use AMovil\Reports\DetallePlanes\ConsolidadoMinutos\Domain\ConsolidadoMinutosRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentConsolidadoMinutosRepository implements ConsolidadoMinutosRepository
{
    public function getReporteByNumCuentaAndPeriodo(string $numCuenta, DateTime $periodo)
    {
        $query = "SELECT A.CUENTA,
        A.MIN_MAX Fecha_inicio_Fecha_Fin,
        trim(a.numero_origen) NRO_TELEFONO,
        trim(a.cantidad_Lineas) CANTIDAD_LINEAS,
        trim(a.CANTIDAD_PLANES) CANTIDAD_PLANES,
        trim(a.PLAN) PLAN,
        case
        when numero_origen <> 'TOTAL' then
        trim(to_char(a.DATOS_GB, 'fm9990.00'))
        ELSE
        trim(a.DATOS_GB)
        end DATOS_GB,
        trim(a.SMS) SMS,
        trim(a.VOZ) VOZ,
        trim(a.HACIA_CLARO) HACIA_CLARO,
        trim(a.RPC) RPC,
        trim(a.movil_otros_oper) MOVIL_OTROS_OPERADORES,
        trim(a.HACIA_FIJOS) HACIA_FIJOS,
        trim(a.LDN) LDN,
        trim(a.LDI) LDI,
        trim(a.SIN_FRONTERA_DATOS) SIN_FRONTERA_DATOS,
        trim(a.SIN_FRONTERA_VOZ) SIN_FRONTERA_VOZ,
        trim(a.SMS_SIN_FRONTERA) SMS_SIN_FRONTERA,
        trim(a.ROAMING_DATOS) ROAMING_DATOS,
        trim(a.ROAMING_SMS) ROAMING_SMS,
        trim(a.DURACION_ROAMING_VOZ) DURACION_ROAMING_VOZ,
        trim(a.ROAMING_VOZ) ROAMING_VOZ
        from DWA.CONSOLIDADO_CORP A
        where trim(PERIODO) = :periodo
        and CUENTA = :num_cuenta";
        $params = ["periodo" => $periodo->format("Ym"), "num_cuenta" => $numCuenta];
        $data = DB::select(DB::raw($query), $params);
        return $data;
    }
}
