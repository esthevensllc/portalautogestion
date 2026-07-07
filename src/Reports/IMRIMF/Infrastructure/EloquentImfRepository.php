<?php

namespace AMovil\Reports\IMRIMF\Infrastructure;

use AMovil\Reports\IMRIMF\Domain\ImfRepository;
use Illuminate\Support\Facades\DB;

class EloquentImfRepository implements ImfRepository
{
    use ConcernsRows;

    public function getHistory(string $telefono): array
    {
        $query = "SELECT
                TO_CHAR(FECHA, 'DD/MM/YYYY HH24:MI:SS') AS FECHA,
                TIPO,
                INTERACCION,
                TOTAL,
                DETALLE
            FROM CLIATC.CI_ACCIONES_IMF_MACRO
            WHERE TELEFONO = :telefono
            ORDER BY FECHA DESC";

        $rows = DB::select(DB::raw($query), ['telefono' => $telefono]);

        return array_map(function ($row) {
            return [
                'fecha' => $this->rowValue($row, 'FECHA'),
                'tipo' => $this->rowValue($row, 'TIPO'),
                'interaccion' => $this->rowValue($row, 'INTERACCION'),
                'total' => (float) ($this->rowValue($row, 'TOTAL') ?? 0),
                'detalle' => $this->rowValue($row, 'DETALLE'),
            ];
        }, $rows);
    }

    public function getSummary(string $telefono): array
    {
        $query = "SELECT
                TIPO,
                COUNT(1) AS CANTIDAD,
                NVL(SUM(TOTAL), 0) AS IMPORTE
            FROM CLIATC.CI_ACCIONES_IMF_MACRO
            WHERE TELEFONO = :telefono
            GROUP BY TIPO
            ORDER BY TIPO";

        $rows = DB::select(DB::raw($query), ['telefono' => $telefono]);

        return array_map(function ($row) {
            return [
                'tipo' => $this->rowValue($row, 'TIPO'),
                'cantidad' => (int) ($this->rowValue($row, 'CANTIDAD') ?? 0),
                'importe' => (float) ($this->rowValue($row, 'IMPORTE') ?? 0),
            ];
        }, $rows);
    }
}
