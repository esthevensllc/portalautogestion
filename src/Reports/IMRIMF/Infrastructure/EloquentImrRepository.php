<?php

namespace AMovil\Reports\IMRIMF\Infrastructure;

use AMovil\Reports\IMRIMF\Domain\ImrRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EloquentImrRepository implements ImrRepository
{
    use ConcernsRows;

    public function getCustomerInfo(string $customerId): ?array
    {
        $partition = $this->currentSubscriberPartition();

        $query = "SELECT
                CUSTOMER_ID_TELEFONO,
                PLAN_TARIFARIO,
                RANGO_ANTIGUEDAD,
                SEGMENTO_VALOR,
                CARGO_FIJO,
                CARGO_FIJO_CALCULADO,
                FACTOR_APLICADO,
                PRODUCTO_FACTOR,
                ES_MAYOR_DOS_ANIOS,
                TIPO_ABONADO
            FROM (
                SELECT
                    DS.CUENTA_CD AS CUSTOMER_ID_TELEFONO,
                    DS.PLAN_DESC AS PLAN_TARIFARIO,
                    TO_CHAR(DS.FECHA_ACTIVACION_SUSCRIPTOR, 'DD/MM/YYYY') AS RANGO_ANTIGUEDAD,
                    FMS.SEGMENTO AS SEGMENTO_VALOR,
                    DS.CARGO_FIJO_SUSCRIPTOR AS CARGO_FIJO,
                    NVL(DS.CARGO_FIJO_SUSCRIPTOR, 0) *
                        NVL(
                            CASE
                                WHEN TRUNC(SYSDATE) > ADD_MONTHS(TRUNC(DS.FECHA_ACTIVACION_SUSCRIPTOR), 24)
                                    THEN SF.FACTOR2
                                ELSE SF.FACTOR
                            END,
                            1
                        ) AS CARGO_FIJO_CALCULADO,
                    NVL(
                        CASE
                            WHEN TRUNC(SYSDATE) > ADD_MONTHS(TRUNC(DS.FECHA_ACTIVACION_SUSCRIPTOR), 24)
                                THEN SF.FACTOR2
                            ELSE SF.FACTOR
                        END,
                        1
                    ) AS FACTOR_APLICADO,
                    'FIJA' AS PRODUCTO_FACTOR,
                    CASE
                        WHEN TRUNC(SYSDATE) > ADD_MONTHS(TRUNC(DS.FECHA_ACTIVACION_SUSCRIPTOR), 24)
                            THEN 1
                        ELSE 0
                    END AS ES_MAYOR_DOS_ANIOS,
                    DS.PLATAFORMA AS TIPO_ABONADO,
                    ROW_NUMBER() OVER (
                        PARTITION BY DS.CUENTA_CD
                        ORDER BY DS.FECHA_ACTIVACION_SUSCRIPTOR DESC
                    ) AS NROW
                FROM DWHDS.DS_SUSCRIPTORES PARTITION ({$partition}) DS
                LEFT JOIN DWA.F_M_SEG_CLIENTES FMS
                    ON FMS.NRO_DOCUMENTO = DS.NUMERO_DOCUMENTO_PARTICIPANTE
                LEFT JOIN USRAES.SEGMENTOS_FACTOR SF
                    ON UPPER(TRIM(SF.PRODUCTO)) = 'FIJA'
                    AND UPPER(TRIM(SF.SEGMENTO)) = UPPER(TRIM(FMS.SEGMENTO))
                WHERE DS.CUENTA_CD = :customer_id
            )
            WHERE NROW = 1";

        $rows = DB::select(DB::raw($query), [
            'customer_id' => trim($customerId),
        ]);

        if (empty($rows)) {
            return null;
        }

        return $this->mapCustomerInfo($rows[0]);
    }

    public function getCustomerInfoByPhone(string $telefono): ?array
    {
        $partition = $this->currentSubscriberPartition();

        $query = "SELECT
                CUSTOMER_ID_TELEFONO,
                PLAN_TARIFARIO,
                RANGO_ANTIGUEDAD,
                SEGMENTO_VALOR,
                CARGO_FIJO,
                CARGO_FIJO_CALCULADO,
                FACTOR_APLICADO,
                PRODUCTO_FACTOR,
                ES_MAYOR_DOS_ANIOS,
                TIPO_ABONADO
            FROM (
                SELECT
                    DS.CUENTA_CD AS CUSTOMER_ID_TELEFONO,
                    DS.PLAN_DESC AS PLAN_TARIFARIO,
                    TO_CHAR(DS.FECHA_ACTIVACION_SUSCRIPTOR, 'DD/MM/YYYY') AS RANGO_ANTIGUEDAD,
                    FMS.SEGMENTO AS SEGMENTO_VALOR,
                    DS.CARGO_FIJO_SUSCRIPTOR AS CARGO_FIJO,
                    NVL(DS.CARGO_FIJO_SUSCRIPTOR, 0) *
                        NVL(
                            CASE
                                WHEN TRUNC(SYSDATE) > ADD_MONTHS(TRUNC(DS.FECHA_ACTIVACION_SUSCRIPTOR), 24)
                                    THEN SF.FACTOR2
                                ELSE SF.FACTOR
                            END,
                            1
                        ) AS CARGO_FIJO_CALCULADO,
                    NVL(
                        CASE
                            WHEN TRUNC(SYSDATE) > ADD_MONTHS(TRUNC(DS.FECHA_ACTIVACION_SUSCRIPTOR), 24)
                                THEN SF.FACTOR2
                            ELSE SF.FACTOR
                        END,
                        1
                    ) AS FACTOR_APLICADO,
                    'MOVIL' AS PRODUCTO_FACTOR,
                    CASE
                        WHEN TRUNC(SYSDATE) > ADD_MONTHS(TRUNC(DS.FECHA_ACTIVACION_SUSCRIPTOR), 24)
                            THEN 1
                        ELSE 0
                    END AS ES_MAYOR_DOS_ANIOS,
                    DS.PLATAFORMA AS TIPO_ABONADO,
                    ROW_NUMBER() OVER (
                        PARTITION BY DS.NUMERO_TELEFONO
                        ORDER BY DS.FECHA_ACTIVACION_SUSCRIPTOR DESC
                    ) AS NROW
                FROM DWHDS.DS_SUSCRIPTORES PARTITION ({$partition}) DS
                LEFT JOIN DWA.F_M_SEG_CLIENTES FMS
                    ON FMS.NRO_DOCUMENTO = DS.NUMERO_DOCUMENTO_PARTICIPANTE
                LEFT JOIN USRAES.SEGMENTOS_FACTOR SF
                    ON UPPER(TRIM(SF.PRODUCTO)) = 'MOVIL'
                    AND UPPER(TRIM(SF.SEGMENTO)) = UPPER(TRIM(FMS.SEGMENTO))
                WHERE DS.NUMERO_TELEFONO = :telefono
            )
            WHERE NROW = 1";

        $rows = DB::select(DB::raw($query), [
            'telefono' => trim($telefono),
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
            FROM CLIATC.CI_PBI_ACCIONES_IMR
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
            FROM CLIATC.CI_PBI_ACCIONES_IMR
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
            'cargo_fijo_calculado' => $this->rowValue($row, 'CARGO_FIJO_CALCULADO'),
            'factor_aplicado' => $this->rowValue($row, 'FACTOR_APLICADO'),
            'producto_factor' => $this->rowValue($row, 'PRODUCTO_FACTOR'),
            'es_mayor_dos_anios' => (int) ($this->rowValue($row, 'ES_MAYOR_DOS_ANIOS') ?? 0) === 1,
            'tipo_abonado' => $this->rowValue($row, 'TIPO_ABONADO'),
        ];
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

