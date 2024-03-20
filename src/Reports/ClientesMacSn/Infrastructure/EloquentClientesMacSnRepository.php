<?php

namespace AMovil\Reports\ClientesMacSn\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ClientesMacSn\Domain\ClientesMacSnRepository;
use AMovil\Shared\Infrastructure\Repository\ClickhouseDB;
use DateTime;

class EloquentClientesMacSnRepository implements ClientesMacSnRepository
{
    private $db;
    private $authService;
    private $userIdentifier;

    public function __construct(ClickhouseDB $db, AuthService $authService)
    {
        $this->db = $db->connection("ch-dn05");
        $this->authService = $authService;
    }

    public function getClientesMacSnSummary()
    {
        $query = "SELECT max(fecha) fecha_max, min(fecha) fecha_min from portal_autogestion.mac_sn_planos_1day";
        return $this->db->select($query)->fetchOne();
    }

    public function getReporte(array $macs, DateTime $fecha)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $this->db->write("DROP TABLE IF EXISTS report_tabla_mac_{$this->userIdentifier}");
        $this->db->write("CREATE TABLE report_tabla_mac_{$this->userIdentifier}(
        mac String
        )
        ENGINE = MergeTree
        PRIMARY KEY mac
        SETTINGS index_granularity = 8192");

        $data = [];
        foreach($macs as $mac){
            $data[] = [$mac];
        }
        $this->db->insert("report_tabla_mac_{$this->userIdentifier}", $data, ["mac"]);

        $strFecha = $fecha->format('Y-m-d');

        $query = "SELECT
            aa.fecha,
            aa.tipo,
            aa.mac_sn,
            bb.tip_documento,
            bb.nro_documento,
            aa.customer_id_codcli,
            aa.plano_tracer plano,
            bb.customer_account_name,
            bb.telefono_claro_social,
            bb.numero_adicional,
            bb.direccion
        from portal_autogestion.mac_sn_planos_1day aa
        left join portal_autogestion.clientes_fijos_1day bb
        on aa.customer_id_codcli=bb.customer_account_sc
        where aa.fecha=bb.fecha and aa.fecha='{$strFecha}' -- <--  INGRESAR LA FECHA QUE SELECCIONARON EN EL PORTAL:
        --and aa.mac_sn in ('64FD966E06F2') -- <-- en caso ingrese uno o varios mac separados por comas.
        and aa.mac_sn in (select mac from report_tabla_mac_{$this->userIdentifier} group by 1) -- <-- en caso ingrese un excel.
        group by 1,2,3,4,5,6,7,8,9,10,11";
        return $this->db->select($query)->rows();
    }
}
