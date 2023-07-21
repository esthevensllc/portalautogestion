<?php

namespace AMovil\Reports\RepRecargas\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\RepRecargas\Domain\ReporteRecargasRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentReporteRecargasRepository implements ReporteRecargasRepository
{
    private $userIdentifier;
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function getDetalle(array $lineas, DateTime $fecha1, DateTime $fecha2)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();

        $str_fecha1 = $fecha1->format("Ymd");
        $str_fecha2 = $fecha2->format("Ymd");
        $this->reloadLineasTable($lineas);
        $queries = [
            ["sql" => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.RECARGAS_TMP1_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;"],
            ["sql" => "CREATE TABLE USRAES.RECARGAS_TMP1_{$this->userIdentifier} AS
            select to_char(RECHARGEDATE, 'dd/mm/yyyy') as RECHARGEDATE,MSISDN,PROID,PRODUCTID,ACC_CHARGE_GENERAL,NUMBERRECHARGEGPA from (select/*+PARALLEL(10)*/ RECHARGEDATE,MSISDN,PROID,PRODUCTID,ACC_CHARGE_GENERAL/100 ACC_CHARGE_GENERAL,NUMBERRECHARGEGPA 
            from usr_admin.a_d_rec_subscriber
            where msisdn IN (SELECT LINEA FROM USRAES.RE_LINEAS_TMP1_{$this->userIdentifier})
            and rechargedate >=TO_DATE('{$str_fecha1}','YYYYMMDD')
            and rechargedate <=TO_DATE('{$str_fecha2}','YYYYMMDD')
            order by rechargedate desc)
            where acc_charge_general<>0"],
        ];
        $this->exec_sql($queries);
        return DB::table("USRAES.RECARGAS_TMP1_{$this->userIdentifier}")->get();
    }

    public function getExtras(array $lineas, DateTime $fecha1, DateTime $fecha2)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();

        $str_fecha1 = $fecha1->format("Ymd");
        $str_fecha2 = $fecha2->format("Ymd");
        $this->reloadLineasTable($lineas);
        $queries = [
            ["sql" => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.RECARGAS_TMP2_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;"],
            ["sql" => "CREATE TABLE USRAES.RECARGAS_TMP2_{$this->userIdentifier} AS
            select /*+PARALLEL(10)*/ to_char(RECHARGEDATE, 'dd/mm/yyyy') as RECHARGEDATE,MSISDN,PROID,PRODUCTID,ACC_CHARGE_GENERAL/100 ACC_CHARGE_GENERAL,NUMBERRECHARGEGPA,
            BONORECARGAVOZ_CHARGE,BONORECARGAVOZ_NUMBER_CHARGE,BONORECARGASMS_CHARGE,BONORECARGASMS_NUMBER_CHARGE,PAQUPROMOC_CHARGE,
            PAQUPROMOC_NUMBER_CHARGE,PAQUPROSMS_CHARGE,PAQUPROSMS_NUMBER_CHARGE,PAQURECUMOC_CHARGE,PAQURECUMOC_NUMBER_CHARGE,
            to_char(creation_date, 'dd/mm/yyyy ')||to_number(to_char(creation_date, 'hh12'))||to_char(creation_date, ':mi:ss PM') as creation_date
             from usr_admin.a_d_rec_subscriber
            where msisdn IN (SELECT LINEA FROM USRAES.RE_LINEAS_TMP1_{$this->userIdentifier})
            and rechargedate >=TO_DATE('{$str_fecha1}','YYYYMMDD')
            and rechargedate <=TO_DATE('{$str_fecha2}','YYYYMMDD')
            order by rechargedate desc"],
        ];
        $this->exec_sql($queries);
        return DB::table("USRAES.RECARGAS_TMP2_{$this->userIdentifier}")->get();
    }

    private function reloadLineasTable(array $lineas)
    {
        $queries = [];
        $queries[] = [
            "sql" => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.RE_LINEAS_TMP1_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;"
        ];
        $queries[] = ["sql" => "CREATE TABLE USRAES.RE_LINEAS_TMP1_{$this->userIdentifier}(LINEA VARCHAR2(30))"];
        
        $lineas_to_insert = [];
        foreach($lineas as $linea){
            $lineas_to_insert[] = ["linea" => $linea];
        }

        $this->exec_sql($queries);
        DB::table("USRAES.RE_LINEAS_TMP1_{$this->userIdentifier}")->insert($lineas_to_insert);
    }

    private function exec_sql(array $plsql)
    {

        foreach($plsql as $row){
            if(array_key_exists('params', $row)){
                DB::statement(DB::Raw($row['sql']), $row['params']);
            }else{
                DB::statement(DB::Raw($row['sql']));
            }
        }
    }
}
