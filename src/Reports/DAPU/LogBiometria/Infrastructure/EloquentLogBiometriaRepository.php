<?php

namespace AMovil\Reports\DAPU\LogBiometria\Infrastructure;

use AMovil\Reports\DAPU\LogBiometria\Domain\LogBiometriaRepository;
use Illuminate\Support\Facades\DB;

class EloquentLogBiometriaRepository implements LogBiometriaRepository
{
    public function getByDni_Periodo($dni, $periodo)
    {
        $values = [$dni];
        $extra_filter = "";
        if($periodo !== null){
            $values[] = $periodo;
            $extra_filter = "and to_char(a.FECHA_REGISTRO_TRANSACCION, 'yyyymm') = ?";
        }
        $query = "select  
        a.numero_documento_persona as DNI, 
        a.FECHA_REGISTRO_TRANSACCION, 
        a.MENSAJE_RESPUESTA,
        a.modelo_dispositivo, 
        a.MARCA_DISPOSITIVO,
        a.VERSION_APLICATIVO,
        a.codigo_aplicativo,
        a.MODELO_ESTACION, 
        a.CODIGO_IDENTI_ESTACION
        from dws.SA_TP_TRANSACCION_LOG a
        where a.numero_documento_persona = ?
        {$extra_filter}
        order by a.FECHA_REGISTRO_TRANSACCION asc";

        return DB::select(DB::raw($query), $values);
    }
}
