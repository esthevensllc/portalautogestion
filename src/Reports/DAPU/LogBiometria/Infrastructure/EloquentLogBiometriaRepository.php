<?php

namespace AMovil\Reports\DAPU\LogBiometria\Infrastructure;

use AMovil\Reports\DAPU\LogBiometria\Domain\LogBiometriaRepository;
use Illuminate\Support\Facades\DB;

class EloquentLogBiometriaRepository implements LogBiometriaRepository
{
    public function getByDniAndMsisdn($dni, $msisdn)
    {
        $query = "SELECT
            FECHA_REGISTRO_TRANSACCION,
            NUM_DOC,
            NUMERO_TRANSACCION,
            CODIGO_RESPUESTA,
            MENSAJE_RESPUESTA,
            MARCA_DISPOSITIVO,
            MODELO_DISPOSITIVO,
            VERSION_APLICATIVO,
            CODIGO_APLICATIVO,
            MODELO_ESTACION,
            CODIGO_IDENTI_ESTACION,
            SUBSCRIPTION_ACCESS_NUMBER,
            AGREEMENT_STATUS_DATE
        FROM (
            SELECT
            A.FECHA_REGISTRO_TRANSACCION,
            A.NUMERO_DOCUMENTO_PERSONA AS NUM_DOC,
            A.NUMERO_TRANSACCION,
            A.CODIGO_RESPUESTA,
            A.MENSAJE_RESPUESTA,
            A.MARCA_DISPOSITIVO,
            A.MODELO_DISPOSITIVO,
            A.VERSION_APLICATIVO,
            A.CODIGO_APLICATIVO,
            A.MODELO_ESTACION,
            A.CODIGO_IDENTI_ESTACION,
            S.SUBSCRIPTION_ACCESS_NUMBER,
            S.AGREEMENT_STATUS_DATE,
            ROW_NUMBER() OVER (PARTITION BY A.NUMERO_DOCUMENTO_PERSONA ORDER BY A.FECHA_REGISTRO_TRANSACCION DESC) AS rn
            FROM DWS.SA_TP_TRANSACCION_LOG A
            LEFT JOIN DWA.DW_M_SUBSCRIPTION S
                ON A.NUMERO_DOCUMENTO_PERSONA = S.ID_CARD_VALUE
            WHERE A.NUMERO_DOCUMENTO_PERSONA = ? --INPUT
                AND A.CODIGO_RESPUESTA = '70006'
                AND A.CODIGO_RESPUESTA NOT IN ('00000')
                AND S.SUBSCRIPTION_ACCESS_NUMBER = ? --INPUT CON 51
                AND A.FECHA_REGISTRO_TRANSACCION < S.AGREEMENT_STATUS_DATE
        ) sub
        WHERE rn = 1
        ORDER BY FECHA_REGISTRO_TRANSACCION ASC";

        $values = [$dni, $msisdn];
        return DB::select(DB::raw($query), $values);
    }
}
