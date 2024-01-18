<?php

namespace AMovil\Reports\ReporteEsim\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ReporteEsim\Domain\ReporteEsimRepository;
use AMovil\Shared\Infrastructure\Repository\ClickhouseDB;
use Illuminate\Support\Facades\DB;

class EloquentReporteEsimRepository implements ReporteEsimRepository
{
    private $connectionId = "ch-dn04";
    private $db;
    private $authService;
    private $userIdentifier;

    public function __construct(ClickhouseDB $db, AuthService $authService)
    {
        $this->db = $db;
        $this->authService = $authService;
    }

    public function getReporte(string $nintex, array $data)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $database = $this->db->connection($this->connectionId);

        DB::connection($this->connectionId)->statement("truncate table udb.base_lineas_reporte_esim");

        $mappedData = [];
        foreach($data as $row){
            $mappedData[] = [$row["fecha"], $row["msisdn"], $row["iccid"], $row["imsi_nuevo"], $row["tac"], $row["descripcion_gsma"]];
        }

        $database->insert("udb.base_lineas_reporte_esim", $mappedData, [
            "fecha", "msisdn", "iccid", "imsi_nuevo", "tac", "descripcion_gsma",
        ]);

        $fechaResult = $database->select("select min(fecha) fecha from udb.base_lineas_reporte_esim")->rows();

        $fecha = $fechaResult[0]['fecha'];

        $database->write("DROP TABLE IF EXISTS base_lineas_reporte_esim_temp_{$this->userIdentifier}");
        $database->write("CREATE TABLE base_lineas_reporte_esim_temp_{$this->userIdentifier}(
        fecha Date,
        msisdn UInt64,
        iccid Nullable(String),
        imsi_nuevo Nullable(String),
        tac Nullable(String),
        descripcion_gsma Nullable(String)
        )
        ENGINE = MergeTree
        PRIMARY KEY msisdn
        SETTINGS index_granularity = 8192");

        $query = "INSERT INTO base_lineas_reporte_esim_temp_{$this->userIdentifier}(fecha, msisdn, iccid, imsi_nuevo, tac, descripcion_gsma)
        SELECT
        a.fecha fecha
        ,a.msisdn msisdn
        ,a.iccid iccid
        ,a.imsi_nuevo imsi_nuevo
        ,if(b.imei is not null, substring(b.imei,1,8), c.tac) tac
        ,if(b.modelo is not null, b.modelo, c.modelo) modelo
        from udb.base_lineas_reporte_esim a
        left join
        (
            select fecha,msisdn,imei,modelo
            from udb.reporte_volte_1day
            where fecha>='{$fecha}' --fecha mas baja
            and substring(toString(msisdn),-9)
            in (select toString(msisdn) from udb.base_lineas_reporte_esim group by 1)
            and imei is not null and imei<>''
        ) b
        on toString(a.msisdn)=substring(toString(b.msisdn),-9) and a.fecha=b.fecha
        left join 
        (
            select x.fecha fecha
            ,y.fecha fecha_act
            ,x.msisdn msisdn
            ,x.iccid iccid
            ,x.imsi_nuevo imsi_nuevo
            ,substring(y.imei,1,8) tac
            ,z.marketing_name modelo
            from udb.base_lineas_reporte_esim x
            join
            (
                select fecha
                ,fono
                ,imei
                ,imsi
                ,(signal_2g_succ+signal_3g_succ+signal_4g_succ+call_mo_2g_succ+call_mo_3g_succ+call_mt_2g_succ+call_mt_3g_succ+traffic_2g_succ+traffic_3g_succ+sms_mt_2g_succ+sms_mt_3g_succ+sms_mo_2g_succ+sms_mo_3g_succ+sms_mo_4g_succ+sms_mt_4g_succ+signal_2g_succ+signal_3g_succ+signal_4g_succ) eventos
                ,row_number() over (partition by fecha,fono order by eventos asc) nrow
                from resumen_eventos.resumen_eventos
                where fecha>='{$fecha}' --fecha mas baja
                and fono in (select msisdn from udb.base_lineas_reporte_esim group by 1)
                and imei is not null and imei<>''
                and (signal_2g_succ+signal_3g_succ+signal_4g_succ+call_mo_2g_succ+call_mo_3g_succ+call_mt_2g_succ+call_mt_3g_succ+traffic_2g_succ+traffic_3g_succ+sms_mt_2g_succ+sms_mt_3g_succ+sms_mo_2g_succ+sms_mo_3g_succ+sms_mo_4g_succ+sms_mt_4g_succ+signal_2g_succ+signal_3g_succ+signal_4g_succ)>0
            ) y
            on x.msisdn=y.fono and x.fecha=y.fecha
            left join
            (
                select * from gsma.equipment_gsma_rx_v2
            ) z
            on substring(y.imei,1,8)=z.tac
            where nrow=1
        ) c
        on toString(a.msisdn)=toString(c.msisdn) and a.iccid=c.iccid and a.imsi_nuevo=c.imsi_nuevo";

        $database->write($query);
        
        $database->write("INSERT INTO udb.base_lineas_reporte_esim_hist(nintex, fecha, msisdn, iccid, imsi_nuevo, tac, descripcion_gsma)
        SELECT :nintex nintex, fecha, msisdn, iccid, imsi_nuevo, tac, descripcion_gsma
        FROM base_lineas_reporte_esim_temp_{$this->userIdentifier}", ["nintex" => $nintex]);

        $result = $database->select("SELECT * FROM base_lineas_reporte_esim_temp_{$this->userIdentifier}")->rows();

        $database->write("DROP TABLE base_lineas_reporte_esim_temp_{$this->userIdentifier}");
        return $result;
    }

    public function nintexIsProcessed($nintex)
    {
        $data = $this->db->connection($this->connectionId)
        ->select("SELECT count(*) counter FROM udb.base_lineas_reporte_esim_hist where nintex = :nintex", ["nintex" => $nintex])->rows();
        return $data[0]["counter"] > 0;
    }
}
