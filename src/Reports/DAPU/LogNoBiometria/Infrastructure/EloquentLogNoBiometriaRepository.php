<?php

namespace AMovil\Reports\DAPU\LogNoBiometria\Infrastructure;

use AMovil\Reports\DAPU\LogNoBiometria\Domain\LogNoBiometriaRepository;
use Illuminate\Support\Facades\DB;

class EloquentLogNoBiometriaRepository implements LogNoBiometriaRepository
{
    public function getByDniAndPeriodo($dni, $periodo)
    {
        // 2️⃣ Intentar detectar el formato
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{1,2}$/', $periodo)) {
            // Tiene hora
            $toChar = "TO_CHAR(BIOM_FECHA_CREA, 'YYYY-MM-DD HH24')";
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodo)) {
            // Solo fecha
            $toChar = "TO_CHAR(BIOM_FECHA_CREA, 'YYYY-MM-DD')";
        } else {
            // Formato inválido
            throw new InvalidArgumentException("Formato de periodo no válido. Debe ser 'Y-m-d' o 'Y-m-d H'");
        }
        
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
            AND $toChar = ?
            ORDER BY A.BIOM_FECHA_CREA
        ";

        $values = [$dni, $periodo];
        return DB::select(DB::raw($query), $values);
    }
}
