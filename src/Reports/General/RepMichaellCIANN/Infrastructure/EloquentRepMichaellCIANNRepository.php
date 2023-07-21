<?php

namespace AMovil\Reports\General\RepMichaellCIANN\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\General\RepMichaellCIANN\Domain\RepMichaellCIANNRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentRepMichaellCIANNRepository implements RepMichaellCIANNRepository
{
    private $authService;
    private $userIdentifier;
    private $connection = "oracle_dbtodb";

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function getLlamadasByNumCuenta_Periodo($numCuenta, DateTime $periodo)
    {
        $this->setUserIdentifier();
        $strPeriodo = $periodo->format("Ym");
        $queries = [];
        $queries[] = $this->dropTableSentence("USRAES.MICHA_TMP_1460_{$this->userIdentifier}");
        $queries[] = ["sql" => "CREATE TABLE USRAES.MICHA_TMP_1460_{$this->userIdentifier} AS select * from TEMP_TAG_1460 where 1=2"];
        $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.MICHA_TMP_1460_{$this->userIdentifier}
            select * from TEMP_TAG_1460 
            where invoicenumber in (select invoicenumber from temp_tag_11 where clientacctno=:p_num_cuenta
            and periodo=:p_periodo);
            COMMIT;
        END;", "params" => ["p_num_cuenta" => $numCuenta, "p_periodo" => $strPeriodo]];
        $this->execSql($queries);
        return DB::connection($this->connection)
        ->table("USRAES.MICHA_TMP_1460_{$this->userIdentifier}")
        ->selectRaw("invoicenumber, msisdn, secuencial, runningmainnumber, to_char(calldate, 'dd/mm/yyyy') calldate, calltime, callorigin, callnumber, calldestination, callduration, callairtime, callland, calltotal, calltype, tariffzone, tarifftime, rtxtype, groupbox, tipollamada")
        ->get();
    }

    public function getSVAByNumCuenta_Periodo($numCuenta, DateTime $periodo)
    {
        $this->setUserIdentifier();
        $strPeriodo = $periodo->format("Ym");
        $queries = [];
        $queries[] = $this->dropTableSentence("USRAES.MICHA_TMP_1480_{$this->userIdentifier}");
        $queries[] = ["sql" => "CREATE TABLE USRAES.MICHA_TMP_1480_{$this->userIdentifier} AS select * from TEMP_TAG_1480 where 1=2"];
        $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.MICHA_TMP_1480_{$this->userIdentifier}
            select * from TEMP_TAG_1480 
            where invoicenumber in (select invoicenumber from temp_tag_11 where clientacctno=:p_num_cuenta
            and periodo=:p_periodo);
            COMMIT;
        END;", "params" => ["p_num_cuenta" => $numCuenta, "p_periodo" => $strPeriodo]];
        $this->execSql($queries);
        return DB::connection($this->connection)
        ->table("USRAES.MICHA_TMP_1480_{$this->userIdentifier}")
        ->selectRaw("invoicenumber, msisdn, secuencial, runningmainnumber, to_char(smsdate, 'dd/mm/yyyy') smsdate, smstime, smsorigin, smsnumber, smsdestination, smsduration, smsairtime, smsland, smstotal, smstype, tariffzone, tarifftime, rtxtype")
        ->get();
    }

    public function getCuotasByNumCuenta_Periodo($numCuenta, DateTime $periodo)
    {
        $this->setUserIdentifier();
        $strPeriodo = $periodo->format("Ym");
        $queries = [];
        $queries[] = $this->dropTableSentence("USRAES.MICHA_TMP_1315_{$this->userIdentifier}");
        $queries[] = ["sql" => "CREATE TABLE USRAES.MICHA_TMP_1315_{$this->userIdentifier} AS select * from TEMP_TAG_1315 where 1=2"];
        $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.MICHA_TMP_1315_{$this->userIdentifier}
            select * from TEMP_TAG_1315 
            where invoicenumber in (select invoicenumber from temp_tag_11 where clientacctno=:p_num_cuenta
            and periodo=:p_periodo);
            COMMIT;
        END;", "params" => ["p_num_cuenta" => $numCuenta, "p_periodo" => $strPeriodo]];
        $this->execSql($queries);
        return DB::connection($this->connection)
        ->table("USRAES.MICHA_TMP_1315_{$this->userIdentifier}")
        ->selectRaw("invoicenumber, secuencial, to_char(fromdate, 'dd/mm/yyyy') fromdate, to_char(todate, 'dd/mm/yyyy') todate, amount, quantity, rateplan, freeusage1, freeusage2, groupbox")
        ->get();
    }

    public function getReporteServiciosByNumCuenta_Periodo($numCuenta, DateTime $periodo)
    {
        $this->setUserIdentifier();
        $strPeriodo = $periodo->format("Ym");
        $queries = [];
        $queries[] = $this->dropTableSentence("USRAES.MICHA_TMP_1425_{$this->userIdentifier}");
        $queries[] = ["sql" => "CREATE TABLE USRAES.MICHA_TMP_1425_{$this->userIdentifier} AS select * from TEMP_TAG_1425 where 1=2"];
        $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.MICHA_TMP_1425_{$this->userIdentifier}
            select * from TEMP_TAG_1425 
            where invoicenumber in (select invoicenumber from temp_tag_11 where clientacctno=:p_num_cuenta
            and periodo=:p_periodo);
            COMMIT;
        END;", "params" => ["p_num_cuenta" => $numCuenta, "p_periodo" => $strPeriodo]];
        $this->execSql($queries);
        return DB::connection($this->connection)
        ->table("USRAES.MICHA_TMP_1425_{$this->userIdentifier}")
        ->selectRaw("invoicenumber, msisdn, secuencial, description, to_char(fromdate, 'dd/mm/yyyy') fromdate, to_char(todate, 'dd/mm/yyyy') todate, amount, rateplan")
        ->get();
    }

    public function getReporteOCCByNumCuenta_Periodo($numCuenta, DateTime $periodo)
    {
        $this->setUserIdentifier();
        $strPeriodo = $periodo->format("Ym");
        $queries = [];
        $queries[] = $this->dropTableSentence("USRAES.MICHA_TMP_1225_{$this->userIdentifier}");
        $queries[] = ["sql" => "CREATE TABLE USRAES.MICHA_TMP_1225_{$this->userIdentifier} AS select * from TEMP_TAG_1225 where 1=2"];
        $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.MICHA_TMP_1225_{$this->userIdentifier}
            select * from TEMP_TAG_1225 
            where invoicenumber in (select invoicenumber from temp_tag_11 where clientacctno=:p_num_cuenta
            and periodo=:p_periodo);
            COMMIT;
        END;", "params" => ["p_num_cuenta" => $numCuenta, "p_periodo" => $strPeriodo]];
        $this->execSql($queries);
        return DB::connection($this->connection)->table("USRAES.MICHA_TMP_1225_{$this->userIdentifier}")->get();
    }

    public function getRoamingByNumCuenta_Periodo($numCuenta, DateTime $periodo)
    {
        $this->setUserIdentifier();
        $strPeriodo = $periodo->format("Ym");
        $queries = [];
        $queries[] = $this->dropTableSentence("USRAES.MICHA_TMP_1470_{$this->userIdentifier}");
        $queries[] = ["sql" => "CREATE TABLE USRAES.MICHA_TMP_1470_{$this->userIdentifier} AS select * from TEMP_TAG_1470 where 1=2"];
        $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.MICHA_TMP_1470_{$this->userIdentifier}
            select * from TEMP_TAG_1470 
            where invoicenumber in (select invoicenumber from temp_tag_11 where clientacctno=:p_num_cuenta
            and periodo=:p_periodo);
            COMMIT;
        END;", "params" => ["p_num_cuenta" => $numCuenta, "p_periodo" => $strPeriodo]];
        $this->execSql($queries);
        return DB::connection($this->connection)
        ->table("USRAES.MICHA_TMP_1470_{$this->userIdentifier}")
        ->selectRaw("invoicenumber, msisdn, secuencial, runningmainnumber, to_char(calldate, 'dd/mm/yyyy') calldate, calltime, callorigin, callnumber, calldestination, callduration, callairtime, callland, calltotal")
        ->get();
    }

    public function getPlanByNumCuenta_Periodo($numCuenta, DateTime $periodo)
    {
        $this->setUserIdentifier();
        $strPeriodo = $periodo->format("Ym");
        $queries = [];
        $queries[] = $this->dropTableSentence("USRAES.MICHA_TMP_1410_{$this->userIdentifier}");
        $queries[] = ["sql" => "CREATE TABLE USRAES.MICHA_TMP_1410_{$this->userIdentifier} AS select * from TEMP_TAG_1410 where 1=2"];
        $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.MICHA_TMP_1410_{$this->userIdentifier}
            select * from TEMP_TAG_1410 
            where invoicenumber in (select invoicenumber from temp_tag_11 where clientacctno=:p_num_cuenta
            and periodo=:p_periodo);
            COMMIT;
        END;", "params" => ["p_num_cuenta" => $numCuenta, "p_periodo" => $strPeriodo]];
        $this->execSql($queries);
        return DB::connection($this->connection)
        ->table("USRAES.MICHA_TMP_1410_{$this->userIdentifier}")
        ->selectRaw("invoicenumber, msisdn, secuencial, description, to_char(fromdate, 'dd/mm/yyyy') fromdate, to_char(todate, 'dd/mm/yyyy') todate, amount, rateplan, freeusage1, freeusage2, freeusage3, freeusage4, groupbox, freeusage5")
        ->get();
    }

    public function getInfoByNumCuenta($numCuenta)
    {
        $data = DB::connection($this->connection)->select("select MAX(periodo) max_period, MIN(periodo) min_period, CYCLE 
        from temp_tag_11 where clientacctno=? GROUP BY CYCLE", [$numCuenta]);
        if(count($data)>0){
            return $data[0];
        }
        return null;
    }

    private function setUserIdentifier()
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
    }

    private function dropTableSentence($table)
    {
        return ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE {$table}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
    }

    private function execSql($queries)
    {
        foreach($queries as $row){
            if(array_key_exists('params', $row)){
                DB::connection($this->connection)->statement(DB::Raw($row['sql']), $row['params']);
            }else{
                DB::connection($this->connection)->statement(DB::Raw($row['sql']));
            }
        }
    }
}
