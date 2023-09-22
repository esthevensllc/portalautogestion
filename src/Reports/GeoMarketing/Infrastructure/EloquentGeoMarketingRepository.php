<?php

namespace AMovil\Reports\GeoMarketing\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\GeoMarketing\Domain\GeoMarketingRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentGeoMarketingRepository implements GeoMarketingRepository
{
    private $db;
    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        /*$config = [
            'host' => '172.19.242.57',
            'port' => '8123',
            'username' => 'default',
            'password' => ''
        ];
        $this->db = new \ClickHouseDB\Client($config);
        $this->db->database('cdrdatos');
        // $this->db->setTimeout(1.5);      // 1 second , support only Int value
        $this->db->setTimeout(60);       // 10 seconds
        $this->db->setConnectTimeOut(5); // 5 seconds
        $this->db->ping(true);*/
    }

    public function getReport($baseFlag, DateTime $fechaIni, DateTime $fechaFin)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $strDay = $fechaIni->format("Ymd");
        $strFechaIni = $fechaIni->format("Y-m-d H:i").":00";
        $strFechaFin = $fechaFin->format("Y-m-d H:i").":00";
        $queries = [];
        $queries[] = [
            "sql" => "DROP TABLE IF EXISTS cdrdatos.usuarios_estadio_{$this->userIdentifier}"
        ];
        $queries[] = [
            "sql" => "CREATE TABLE cdrdatos.usuarios_estadio_{$this->userIdentifier}(
                `msisdn` Nullable(UInt64) DEFAULT NULL CODEC(T64, LZ4),
                `record_time` DateTime DEFAULT '0000-00-00 00:00:00'
            )
            ENGINE = MergeTree
            PRIMARY KEY msisdn
            ORDER BY msisdn
            SETTINGS index_granularity = 8192,
            allow_nullable_key = 1"
        ];
        $queries[] = [
            "sql" => "INSERT INTO cdrdatos.usuarios_estadio_{$this->userIdentifier}
            SELECT a.served_msisdn AS MSISDN,
            max(a.record_opening_time) AS RECORD_TIME  
            FROM cdrdatos.cdr{$strDay} a
            INNER JOIN mlac.base_place_celdas_pap b
            ON toInt64(a.uli_sac+a.uli_ci+a.uli_ecgi) = toInt64(b.cellid) AND  toInt64(a.uli_lac+a.uli_tai) = toInt64(b.lac_tac)  
            WHERE a.record_opening_time >='{$strFechaIni}' and a.record_opening_time <= '{$strFechaFin}' and b.flag_place = '{$baseFlag}'
            group by 1"
        ];
        $queries[] = [
            "sql" => "INSERT INTO cdrdatos.usuarios_estadio_{$this->userIdentifier}
            select x.msisdn2,max(x.fecha_registro) from
            (
             select CONCAT('51',toString(msisdn)) as msisdn2,fromUnixTimestamp(starttime) as fecha_registro,
             reinterpretAsUInt64(reverse(unhex(ci))) as cellid,
             reinterpretAsUInt64(reverse(unhex(lac))) as lac
             from chrs.lu_msc_{$strDay}
             where ci is not null and lac is not null and length(toString(msisdn))=9
             and fromUnixTimestamp(starttime) >= '{$strFechaIni}' and fromUnixTimestamp(starttime) <= '{$strFechaFin}'
             group by 1,2,3,4
            ) x
            INNER JOIN  mlac.base_place_celdas_pap y
            ON toInt64(x.cellid) = toInt64(y.cellid) AND  toInt64(x.lac) = toInt64(y.lac_tac)
            where x.msisdn2 not in (select msisdn from cdrdatos.usuarios_estadio_{$this->userIdentifier} group by 1)
            and y.flag_place = '{$baseFlag}'
            group by 1"
        ];
        $this->exec_sql($queries);

        $sql = "SELECT msisdn FROM cdrdatos.usuarios_estadio_{$this->userIdentifier} where toString(msisdn) like '519%' group by 1";
        $data = DB::connection("ch-dn02")->select($sql);

        $queries = [];
        $queries[] = [
            "sql" => "DROP TABLE cdrdatos.usuarios_estadio_{$this->userIdentifier}"
        ];
        $this->exec_sql($queries);
        return $data;
    }

    public function saveLog($nintex, $base, DateTime $fechaIni, DateTime $fechaFin, DateTime $createAt, $filename)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        DB::connection("ch-dn02")->table("cdrdatos.table_nintex_geomarketing")
        ->insert([
            "codigo_c" => $this->userIdentifier,
            "nintex" => $nintex,
            "base" => $base,
            "fecha_inicio" => $fechaIni->format("Y-m-d H:i:s"),
            "fecha_fin" => $fechaFin->format("Y-m-d H:i:s"),
            "created_at" => $createAt->format("Y-m-d H:i:s"),
            "filename_exported" => $filename
        ]);
    }

    private function exec_sql(array $queries)
    {
        foreach($queries as $row){
            DB::connection("ch-dn02")->statement(DB::raw($row["sql"]));
        }
    }
}
