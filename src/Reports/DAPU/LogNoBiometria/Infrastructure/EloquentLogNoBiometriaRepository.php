<?php

namespace AMovil\Reports\DAPU\LogNoBiometria\Infrastructure;

use AMovil\Reports\DAPU\LogNoBiometria\Domain\LogNoBiometriaRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentLogNoBiometriaRepository implements LogNoBiometriaRepository
{
    public function getByDniAndPeriodo($dni, DateTime $periodo)
    {
        $strPeriodo = $periodo->format("d/m/Y H");
        $query = "SELECT /*+ PARALLEL(8) */
        A.BIOM_TIPOVALIDACION,
        A.BIOM_IDPADRE,
        A.BIOM_RPTAVAL,
        A.BIOM_APLICACION,
        A.BIOM_NRODOCUMENTO,
        A.BIOM_OBSERVACION,
        A.BIOM_FECHA_CREA,
        A.BIOM_CODIGO
        FROM DWS.SA_BIOMT_BIOMETRIA A
        WHERE A.BIOM_TIPOVALIDACION = 'NOBIORENIEC'
        AND BIOM_RPTAVAL IN ('0')
        AND A.BIOM_NRODOCUMENTO = ?
        AND TO_CHAR(BIOM_FECHA_CREA, 'DD/MM/YYYY HH24') = ?
        ORDER BY A.BIOM_FECHA_CREA
        ";

        $values = [$dni, $strPeriodo];
        return DB::select(DB::raw($query), $values);
    }
}
