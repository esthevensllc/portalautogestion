<?php

namespace AMovil\Reports\IMRIMF\Infrastructure;

use AMovil\Reports\IMRIMF\Domain\ImfRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EloquentImfRepository implements ImfRepository
{
    use ConcernsRows;

    public function getCustomerInfo(string $telefono): ?array
    {
        $partition = $this->currentSubscriberPartition();
        $telefonoConCodigoPais = $this->normalizeSubscriberPhone($telefono);

        $query = "SELECT
                CUSTOMER_ID_TELEFONO,
                PLAN_TARIFARIO,
                RANGO_ANTIGUEDAD,
                SEGMENTO_VALOR,
                CARGO_FIJO,
                TIPO_ABONADO
            FROM (
                SELECT
                    DS.CUENTA_CD AS CUSTOMER_ID_TELEFONO,
                    DS.PLAN_DESC AS PLAN_TARIFARIO,
                    TO_CHAR(DS.FECHA_ACTIVACION_SUSCRIPTOR, 'DD/MM/YYYY') AS RANGO_ANTIGUEDAD,
                    FMS.SEGMENTO AS SEGMENTO_VALOR,
                    DS.CARGO_FIJO_SUSCRIPTOR AS CARGO_FIJO,
                    DS.PLATAFORMA AS TIPO_ABONADO,
                    ROW_NUMBER() OVER (
                        PARTITION BY DS.NUMERO_TELEFONO
                        ORDER BY DS.FECHA_ACTIVACION_SUSCRIPTOR DESC
                    ) AS NROW
                FROM DWHDS.DS_SUSCRIPTORES PARTITION ({$partition}) DS
                LEFT JOIN DWA.F_M_SEG_CLIENTES FMS
                    ON FMS.NRO_DOCUMENTO = DS.NUMERO_DOCUMENTO_PARTICIPANTE
                WHERE DS.NUMERO_TELEFONO = :telefono
            )
            WHERE NROW = 1";

        $rows = DB::select(DB::raw($query), [
            'telefono' => $telefonoConCodigoPais,
        ]);

        if (empty($rows)) {
            return null;
        }

        return $this->mapCustomerInfo($rows[0]);
    }

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

    private function mapCustomerInfo(object $row): array
    {
        return [
            'customer_id_telefono' => $this->rowValue($row, 'CUSTOMER_ID_TELEFONO'),
            'plan_tarifario' => $this->rowValue($row, 'PLAN_TARIFARIO'),
            'rango_antiguedad' => $this->rowValue($row, 'RANGO_ANTIGUEDAD'),
            'segmento_valor' => $this->rowValue($row, 'SEGMENTO_VALOR'),
            'cargo_fijo' => $this->rowValue($row, 'CARGO_FIJO'),
            'tipo_abonado' => $this->rowValue($row, 'TIPO_ABONADO'),
        ];
    }

    private function normalizeSubscriberPhone(string $telefono): string
    {
        $digits = preg_replace('/\D+/', '', $telefono) ?: '';

        if ($digits === '') {
            return trim($telefono);
        }

        return substr($digits, 0, 2) === '51' ? $digits : '51' . $digits;
    }

    private function currentSubscriberPartition(): string
    {
        $partition = 'P_' . now()->format('Ym');

        if (!preg_match('/^P_\d{6}$/', $partition)) {
            throw new RuntimeException('La partición de suscriptores no tiene un formato válido.');
        }

        return $partition;
    }
}
