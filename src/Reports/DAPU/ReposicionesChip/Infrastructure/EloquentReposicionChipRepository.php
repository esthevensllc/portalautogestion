<?php

namespace AMovil\Reports\DAPU\ReposicionesChip\Infrastructure;

use AMovil\Reports\DAPU\ReposicionesChip\Domain\ReposicionChipRepository;
use Illuminate\Support\Facades\DB;

class EloquentReposicionChipRepository implements ReposicionChipRepository
{
public function getByLinea(string $linea)
    {
        $query = "SELECT /*+ PARALLEL(8) */
            TI.X_CLASE_CODE AS CODIGO_SUBCLASE,
            DECODE(TI.X_SUBCLASE_CODE, '123390','POSTPAGO', 'PREPAGO') AS TIPO_LINEA,
            TI.REASON_3 AS DESC_TIPIFICACION,
            TI.CREATE_DATE AS FECHA_TRANSACCION,
            TI.PHONE AS NUMERO_TELEFONO,
            TP.X_INTER_4 TIPO,
            TP.X_INTER_15 PUNTO_ATENCION,
            TP.X_ICCID,
            TI.INTERACT_ID AS CODIGO_TIPIFICACION,
            TI.S_AGENT AS USUARIO_REG
        FROM DMRED.TABLE_INTERACT TI
        INNER JOIN DMRED.TABLE_X_PLUS_INTER TP ON TI.OBJID=TP.X_PLUS_INTER2INTERACT
        WHERE TI.X_SUBCLASE_CODE IN ('123390', '109267')
        AND TI.PHONE = ?
        ";

        $values = [$linea];
        return DB::select(DB::raw($query), $values);
    }
}
