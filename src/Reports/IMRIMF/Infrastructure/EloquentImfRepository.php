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

    public function getCustomerContextByCustomerId(string $customerId): ?array
    {
        $partition = $this->currentSubscriberPartition();

        $query = "SELECT
                CUSTOMER_ID_TELEFONO,
                PLAN_TARIFARIO,
                RANGO_ANTIGUEDAD,
                SEGMENTO_VALOR,
                CARGO_FIJO,
                TIPO_ABONADO,
                ACTIONS_PHONE,
                FECHA_ACTIVACION_ORDEN
            FROM (
                SELECT
                    DS.CUENTA_CD AS CUSTOMER_ID_TELEFONO,
                    DS.PLAN_DESC AS PLAN_TARIFARIO,
                    TO_CHAR(DS.FECHA_ACTIVACION_SUSCRIPTOR, 'DD/MM/YYYY') AS RANGO_ANTIGUEDAD,
                    FMS.SEGMENTO AS SEGMENTO_VALOR,
                    DS.CARGO_FIJO_SUSCRIPTOR AS CARGO_FIJO,
                    DS.PLATAFORMA AS TIPO_ABONADO,
                    DS.NUMERO_TELEFONO AS ACTIONS_PHONE,
                    DS.FECHA_ACTIVACION_SUSCRIPTOR AS FECHA_ACTIVACION_ORDEN,
                    ROW_NUMBER() OVER (
                        PARTITION BY DS.NUMERO_TELEFONO
                        ORDER BY DS.FECHA_ACTIVACION_SUSCRIPTOR DESC
                    ) AS NROW
                FROM DWHDS.DS_SUSCRIPTORES PARTITION ({$partition}) DS
                LEFT JOIN DWA.F_M_SEG_CLIENTES FMS
                    ON FMS.NRO_DOCUMENTO = DS.NUMERO_DOCUMENTO_PARTICIPANTE
                WHERE DS.CUENTA_CD = :customer_id
            )
            WHERE NROW = 1
            ORDER BY FECHA_ACTIVACION_ORDEN DESC";

        $rows = DB::select(DB::raw($query), [
            'customer_id' => trim($customerId),
        ]);

        if (empty($rows)) {
            return null;
        }

        $actionIdentifiers = [];

        foreach ($rows as $row) {
            $phone = (string) ($this->rowValue($row, 'ACTIONS_PHONE') ?? '');
            $actionIdentifiers = array_merge(
                $actionIdentifiers,
                $this->buildPhoneIdentifierCandidates($phone)
            );
        }

        return [
            'customer_info' => $this->mapCustomerInfo($rows[0]),
            'action_identifiers' => array_values(array_unique($actionIdentifiers)),
        ];
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

        return $this->mapHistoryRows($rows);
    }

    public function getHistoryByIdentifiers(array $identifiers): array
    {
        [$placeholders, $bindings] = $this->buildIdentifierBindings($identifiers);

        if ($placeholders === []) {
            return [];
        }

        $query = "SELECT
                TO_CHAR(FECHA, 'DD/MM/YYYY HH24:MI:SS') AS FECHA,
                TIPO,
                INTERACCION,
                TOTAL,
                DETALLE
            FROM CLIATC.CI_ACCIONES_IMF_MACRO
            WHERE TELEFONO IN (" . implode(', ', $placeholders) . ")
            ORDER BY FECHA DESC";

        $rows = DB::select(DB::raw($query), $bindings);

        return $this->mapHistoryRows($rows);
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

        return $this->mapSummaryRows($rows);
    }

    public function getSummaryByIdentifiers(array $identifiers): array
    {
        [$placeholders, $bindings] = $this->buildIdentifierBindings($identifiers);

        if ($placeholders === []) {
            return [];
        }

        $query = "SELECT
                TIPO,
                COUNT(1) AS CANTIDAD,
                NVL(SUM(TOTAL), 0) AS IMPORTE
            FROM CLIATC.CI_ACCIONES_IMF_MACRO
            WHERE TELEFONO IN (" . implode(', ', $placeholders) . ")
            GROUP BY TIPO
            ORDER BY TIPO";

        $rows = DB::select(DB::raw($query), $bindings);

        return $this->mapSummaryRows($rows);
    }

    private function mapHistoryRows(array $rows): array
    {
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

    private function mapSummaryRows(array $rows): array
    {
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

    private function buildIdentifierBindings(array $identifiers): array
    {
        $normalizedIdentifiers = [];

        foreach ($identifiers as $identifier) {
            if (!is_scalar($identifier)) {
                continue;
            }

            $normalizedIdentifier = $this->normalizeActionIdentifier((string) $identifier);

            if ($normalizedIdentifier !== '') {
                $normalizedIdentifiers[] = $normalizedIdentifier;
            }
        }

        $normalizedIdentifiers = array_values(array_unique($normalizedIdentifiers));
        $placeholders = [];
        $bindings = [];

        foreach ($normalizedIdentifiers as $index => $identifier) {
            $bindingName = 'identifier_' . $index;
            $placeholders[] = ':' . $bindingName;
            $bindings[$bindingName] = $identifier;
        }

        return [$placeholders, $bindings];
    }

    private function buildPhoneIdentifierCandidates(string $telefono): array
    {
        $digits = preg_replace('/\D+/', '', $telefono) ?: '';

        if ($digits === '') {
            return [];
        }

        $candidates = [$digits];

        if (substr($digits, 0, 2) === '51' && strlen($digits) > 2) {
            $candidates[] = substr($digits, 2);
        } else {
            $candidates[] = '51' . $digits;
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function normalizeActionIdentifier(string $identifier): string
    {
        $identifier = strtoupper(trim($identifier));

        if (preg_match('/^H[0-9]+$/', $identifier)) {
            return $identifier;
        }

        return preg_replace('/\D+/', '', $identifier) ?: '';
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
