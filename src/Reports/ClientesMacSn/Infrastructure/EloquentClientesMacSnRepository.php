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

        $this->db->write("DROP TABLE IF EXISTS portal_autogestion.temp_mac_sn_{$this->userIdentifier}");
        $this->db->write("CREATE TABLE portal_autogestion.temp_mac_sn_{$this->userIdentifier}(
            mac_sn Nullable(String) DEFAULT NULL CODEC(LZ4),
            customer_id_codcli Nullable(String) DEFAULT NULL CODEC(LZ4)
        )
        ENGINE = MergeTree
        PRIMARY KEY mac_sn
        SETTINGS index_granularity = 8192, allow_nullable_key = 1");

        $strFecha = $fecha->format('Y-m-d');

        $this->db->write("INSERT INTO portal_autogestion.temp_mac_sn_{$this->userIdentifier}
        select mac_sn,customer_id_codcli 
        from portal_autogestion.mac_sn_planos_1day
        where fecha='{$strFecha}'
        and mac_sn in (select mac from report_tabla_mac_{$this->userIdentifier} group by 1)");

        $query = "SELECT
        aa.mac_sn as mac_sn,
        aa.customer_id_codcli as customer_id_codcli,
        bb.modalidad as modalidad,
        bb.customer_account_name as customer_account_name,
        bb.tip_documento as tip_documento,
        bb.nro_documento as nro_documento,
        bb.direccion as direccion,
        bb.fecha_activacion as fecha_activacion,
        bb.estado as estado,
        bb.fecha_status as fecha_status,
        bb.motivo_de_estado as motivo_de_estado
        from portal_autogestion.temp_mac_sn_{$this->userIdentifier} aa 
        left join 
        (
            select * from (
            select modalidad,
                customer_account_high_sc customer_id_codcli,
                customer_account_name,
                tip_documento,
                nro_documento,
                direccion,
                fecha_activacion,
                estado,
                fecha_status,
                motivo_de_estado 
            from portal_autogestion.clientes_fijos_1day 
            where fecha='{$strFecha}' and customer_account_high_sc in 
                (
                select customer_id_codcli 
                from portal_autogestion.temp_mac_sn_{$this->userIdentifier}
                where customer_id_codcli like '0%'
                )
            union all 
            select modalidad,
                customer_account_sc customer_id_codcli,
                customer_account_name,
                tip_documento,
                nro_documento,
                direccion,
                fecha_activacion,
                estado,
                fecha_status,
                motivo_de_estado 
            from portal_autogestion.clientes_fijos_1day 
            where fecha='{$strFecha}' and customer_account_sc in 
                (
                select customer_id_codcli 
                from portal_autogestion.temp_mac_sn_{$this->userIdentifier}
                where customer_id_codcli not like '0%'
                )
            ) group by 1,2,3,4,5,6,7,8,9,10 
        ) bb 
        on aa.customer_id_codcli=bb.customer_id_codcli";
        
        $data = $this->db->select($query)->rows();

        $this->db->write("DROP TABLE report_tabla_mac_{$this->userIdentifier}");
        $this->db->write("DROP TABLE portal_autogestion.temp_mac_sn_{$this->userIdentifier}");
        return $data;
    }
}
