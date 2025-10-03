<?php

namespace AMovil\Reports\DAPU\LogBiometria\Infrastructure;

use AMovil\Reports\DAPU\LogBiometria\Domain\LogBiometriaRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentLogBiometriaRepository implements LogBiometriaRepository
{
    public function getByDniAndPeriodo($dni, DateTime $periodo)
    {
        $strPeriodo = $periodo->format("d/m/Y H");
        $query = "SELECT /*+ PARALLEL(8) */ 
        A.BIOM_TIPOVALIDACION,
        A.BIOM_IDPADRE,
        A.BIOM_CODBIO,
        A.BIOM_MENSAJE,
        A.BIOM_APLICACION,
        A.BIOM_NRODOCUMENTO,
        A.BIOM_NROTRANSAC,
        A.BIOM_FECHA_CREA,
        A.BIOM_CODIGO
        FROM DWS.SA_BIOMT_BIOMETRIA A
        WHERE A.BIOM_TIPOVALIDACION = 'BIOMETRIA'
        AND A.BIOM_CODBIO IN ('70006', '00000')
        AND A.BIOM_NRODOCUMENTO = ?
        AND TO_CHAR(BIOM_FECHA_CREA, 'DD/MM/YYYY HH24') = ?
        ORDER BY A.BIOM_FECHA_CREA
        ";

        $values = [$dni, $strPeriodo];
        return DB::select(DB::raw($query), $values);
    }
}
