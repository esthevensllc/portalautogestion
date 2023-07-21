<?php

namespace AMovil\Reports\CajaArequipa\FacturaDetallada\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\CajaArequipa\FacturaDetallada\Domain\FacturaDetalladaRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentFacturaDetalladaRepository implements FacturaDetalladaRepository
{
    private $userIdentifier;
    private $authService;
    private $connection = "oracle_dbtodb";

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function getCuotasByNumCuentaAndPeriodo($numCuenta, DateTime $periodo)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();

        $str_periodo = $periodo->format("Ym");
        $queries = [];
        $queries[] = $this->dropTable("USRAES.TEMP_TAG_1315_{$this->userIdentifier}");
        $queries[] = ["sql" => "CREATE TABLE USRAES.TEMP_TAG_1315_{$this->userIdentifier} AS select * from TEMP_TAG_1315 where 1=2"];
        $queries[] = ["sql" => "INSERT INTO USRAES.TEMP_TAG_1315_{$this->userIdentifier}
        select * from TEMP_TAG_1315 
        where invoicenumber in (select invoicenumber from temp_tag_11 where clientacctno = :p_cliente
        and periodo = :p_periodo)", "params" => ["p_cliente" => $numCuenta, "p_periodo" => $str_periodo]];

        $this->exec_sql($queries);
        return DB::connection($this->connection)->table("USRAES.TEMP_TAG_1315_{$this->userIdentifier}")->get();
    }

    public function getServiciosByNumCuentaAndPeriodo($numCuenta, DateTime $periodo)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();

        $str_periodo = $periodo->format("Ym");
        $queries = [];
        $queries[] = $this->dropTable("USRAES.TEMP_TAG_1425_{$this->userIdentifier}");
        $queries[] = ["sql" => "CREATE TABLE USRAES.TEMP_TAG_1425_{$this->userIdentifier} AS select * from TEMP_TAG_1425 where 1=2"];
        $queries[] = ["sql" => "INSERT INTO USRAES.TEMP_TAG_1425_{$this->userIdentifier}
        select * from TEMP_TAG_1425 
        where invoicenumber in (select invoicenumber from temp_tag_11 where clientacctno=:p_cliente
        and periodo=:p_periodo)", "params" => ["p_cliente" => $numCuenta, "p_periodo" => $str_periodo]];

        $this->exec_sql($queries);
        return DB::connection($this->connection)->table("USRAES.TEMP_TAG_1425_{$this->userIdentifier}")->get();
    }

    public function getOCCByNumCuentaAndPeriodo($numCuenta, DateTime $periodo)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();

        $str_periodo = $periodo->format("Ym");
        $queries = [];
        $queries[] = $this->dropTable("USRAES.TEMP_TAG_1225_{$this->userIdentifier}");
        $queries[] = ["sql" => "CREATE TABLE USRAES.TEMP_TAG_1225_{$this->userIdentifier} AS select * from TEMP_TAG_1225 where 1=2"];
        $queries[] = ["sql" => "INSERT INTO USRAES.TEMP_TAG_1225_{$this->userIdentifier}
        select * from TEMP_TAG_1225 
        where invoicenumber in (select invoicenumber from temp_tag_11 where clientacctno=:p_cliente
        and periodo=:p_periodo)", "params" => ["p_cliente" => $numCuenta, "p_periodo" => $str_periodo]];

        $this->exec_sql($queries);
        return DB::connection($this->connection)->table("USRAES.TEMP_TAG_1225_{$this->userIdentifier}")->get();
    }

    public function getRoamingByNumCuentaAndPeriodo($numCuenta, DateTime $periodo)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();

        $str_periodo = $periodo->format("Ym");
        $queries = [];
        $queries[] = $this->dropTable("USRAES.TEMP_TAG_1470_{$this->userIdentifier}");
        $queries[] = ["sql" => "CREATE TABLE USRAES.TEMP_TAG_1470_{$this->userIdentifier} AS select * from TEMP_TAG_1470 where 1=2"];
        $queries[] = ["sql" => "INSERT INTO USRAES.TEMP_TAG_1470_{$this->userIdentifier}
        select * from TEMP_TAG_1470 
        where invoicenumber in (select invoicenumber from temp_tag_11 where clientacctno=:p_cliente
        and periodo=:p_periodo)", "params" => ["p_cliente" => $numCuenta, "p_periodo" => $str_periodo]];

        $this->exec_sql($queries);
        return DB::connection($this->connection)->table("USRAES.TEMP_TAG_1470_{$this->userIdentifier}")->get();
    }

    public function getPlanByNumCuentaAndPeriodo($numCuenta, DateTime $periodo)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();

        $str_periodo = $periodo->format("Ym");
        $queries = [];
        $queries[] = $this->dropTable("USRAES.TEMP_TAG_1410_{$this->userIdentifier}");
        $queries[] = ["sql" => "CREATE TABLE USRAES.TEMP_TAG_1410_{$this->userIdentifier} AS select * from TEMP_TAG_1410 where 1=2"];
        $queries[] = ["sql" => "INSERT INTO USRAES.TEMP_TAG_1410_{$this->userIdentifier}
        select * from TEMP_TAG_1410 
        where invoicenumber in (select invoicenumber from temp_tag_11 where clientacctno=:p_cliente
        and periodo=:p_periodo)", "params" => ["p_cliente" => $numCuenta, "p_periodo" => $str_periodo]];

        $this->exec_sql($queries);
        return DB::connection($this->connection)->table("USRAES.TEMP_TAG_1410_{$this->userIdentifier}")->get();
    }


    private function dropTable(string $table){
        return ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE {$table}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN
                RAISE;
            END IF;
        END;"];
    }

    private function exec_sql($queries){
        foreach($queries as $row){
            if(array_key_exists('params', $row)){
                DB::connection($this->connection)->statement(DB::Raw($row['sql']), $row['params']);
            }else{
                DB::connection($this->connection)->statement(DB::Raw($row['sql']));
            }
        }
    }
}
