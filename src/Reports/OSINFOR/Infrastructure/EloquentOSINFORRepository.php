<?php

namespace AMovil\Reports\OSINFOR\Infrastructure;

use AMovil\Reports\OSINFOR\Domain\OSINFORRepository;
use AMovil\Shared\Infrastructure\Repository\ClickhouseDB;

class EloquentOSINFORRepository implements OSINFORRepository
{
    private const CONNECTION_ID = 'ch-dn05';

    private ClickhouseDB $db;

    public function __construct(ClickhouseDB $db)
    {
        $this->db = $db;
    }

    public function getAvailableRange(string $codigoCliente): array
    {
        $database = $this->db->connection(self::CONNECTION_ID);
        $params = ['codigo_cliente' => $codigoCliente];

        $minRows = $database->select(
            "SELECT y, semana
            FROM hfc.reporte_cluster_fija
            WHERE CODIGO_CLIENTE = :codigo_cliente
            ORDER BY toUInt64(y), toUInt64(semana)
            LIMIT 1",
            $params
        )->rows();

        $maxRows = $database->select(
            "SELECT y, semana
            FROM hfc.reporte_cluster_fija
            WHERE CODIGO_CLIENTE = :codigo_cliente
            ORDER BY toUInt64(y) DESC, toUInt64(semana) DESC
            LIMIT 1",
            $params
        )->rows();

        return [
            'min' => $minRows[0] ?? null,
            'max' => $maxRows[0] ?? null,
        ];
    }

    public function existsByYearAndWeek(string $codigoCliente, string $anio, string $semana): bool
    {
        $database = $this->db->connection(self::CONNECTION_ID);
        $rows = $database->select(
            "SELECT count(*) AS counter
            FROM hfc.reporte_cluster_fija
            WHERE CODIGO_CLIENTE = :codigo_cliente
                AND toUInt64(y) = toUInt64(:anio)
                AND toUInt64(semana) = toUInt64(:semana)",
            [
                'codigo_cliente' => $codigoCliente,
                'anio' => $anio,
                'semana' => $semana,
            ]
        )->rows();

        return ((int) ($rows[0]['counter'] ?? 0)) > 0;
    }

    public function getReporte(string $codigoCliente, string $anio, string $semana): array
    {
        $database = $this->db->connection(self::CONNECTION_ID);

        return $database->select(
            "SELECT CODIGO_CLIENTE, y, semana, tipo, traffic/1024 AS trafico_mb
            FROM hfc.reporte_cluster_fija
            WHERE CODIGO_CLIENTE = :codigo_cliente
                AND toUInt64(y) = toUInt64(:anio)
                AND toUInt64(semana) >= toUInt64(:semana)
            ORDER BY toUInt64(y), toUInt64(semana)",
            [
                'codigo_cliente' => $codigoCliente,
                'anio' => $anio,
                'semana' => $semana,
            ]
        )->rows();
    }
}
