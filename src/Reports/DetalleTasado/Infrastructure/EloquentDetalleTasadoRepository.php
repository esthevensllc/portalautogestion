<?php

namespace AMovil\Reports\DetalleTasado\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\DetalleTasado\Domain\DetalleTasadoRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentDetalleTasadoRepository implements DetalleTasadoRepository
{
    private $connection = "oracle";
    private $userIdentifier;
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function getReporte($numerosCuenta, DateTime $fechaIni, DateTime $fechaFin)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $strPeriodo = $fechaIni->format("Ym");
        $strDates = $fechaIni->format("d/m/Y");
        if($fechaIni->format("d/m/Y") !== $fechaFin->format("d/m/Y")){
            $strDates .= " - ".$fechaFin->format("d/m/Y");
        }

        $params = [];
        $strParams = [];
        foreach($numerosCuenta as $value){
            $params["p_1"] = $value;
            $strParams[] = ":p_1";
        }
        $strParams = implode(",", $strParams);

        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.LINEAS_DE_LAS_CUENTAS_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = [
            "sql" => "CREATE TABLE USRAES.LINEAS_DE_LAS_CUENTAS_{$this->userIdentifier}(
                SUBSCRIPTION_ACCESS_NUMBER VARCHAR2(250)
            )"
        ];
        $queries[] = [
            "sql" => "BEGIN
                INSERT INTO USRAES.LINEAS_DE_LAS_CUENTAS_{$this->userIdentifier}(SUBSCRIPTION_ACCESS_NUMBER)
                select SUBSCRIPTION_ACCESS_NUMBER from  DWA.DW_M_SUBSCRIPTION_HIST PARTITION(P_{$strPeriodo}) 
                where CUSTOMER_ACCOUNT_DESC in ({$strParams})
                and AGREEMENT_STATUS<>'D'
                group by SUBSCRIPTION_ACCESS_NUMBER;
                COMMIT;
            END;",
            "params" => $params
        ];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TRAFICO_TASADO_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = [
            "sql" => "CREATE TABLE USRAES.TRAFICO_TASADO_{$this->userIdentifier}(
                FCH_TRAFICO DATE,
                MSISDN VARCHAR2(250),
                PLAN VARCHAR2(250),
                SERVICIO_DES VARCHAR2(250),
                TRAFICO_BYTES NUMBER
            )"
        ];

        $fechaRecorrido = (clone $fechaIni);
        while ($fechaRecorrido->format("Ymd") <= $fechaFin->format("Ymd")) {
            $strDay = $fechaRecorrido->format("Ymd");
            $queries[] = [
                "sql" => "BEGIN
                    INSERT INTO USRAES.TRAFICO_TASADO_{$this->userIdentifier}(FCH_TRAFICO, MSISDN, PLAN, SERVICIO_DES, TRAFICO_BYTES)
                    SELECT to_date('{$strDay}', 'yyyymmdd') FCH_TRAFICO, MSISDN,
                    SUS_PLAN_DES PLAN, 
                    SERVICIO_DES,
                    SUM(VOL_DEBITADO_NUM) TRAFICO_BYTES
                    FROM DWA.AYF_A_D_TRAFICO_DATOS_POSTPAGO PARTITION (P_{$strDay})
                    WHERE MSISDN in (
                        select SUBSCRIPTION_ACCESS_NUMBER from USRAES.LINEAS_DE_LAS_CUENTAS_{$this->userIdentifier}
                        group by SUBSCRIPTION_ACCESS_NUMBER
                    )
                    group by MSISDN, SUS_PLAN_DES, SERVICIO_DES;
                    COMMIT;
                END;"
            ];
            $fechaRecorrido->modify("+1 day");
        }

        $queries[] = [
            "sql" => "DECLARE
                V_COUNT NUMBER;
                V_PLAN VARCHAR2(250);
                V_SERVICIO_DES VARCHAR2(250);
            BEGIN
                SELECT COUNT(*) INTO V_COUNT FROM USRAES.TRAFICO_TASADO_{$this->userIdentifier};
                IF V_COUNT > 0 THEN
                    SELECT PLAN, SERVICIO_DES INTO V_PLAN, V_SERVICIO_DES FROM (
                        SELECT PLAN, SERVICIO_DES FROM USRAES.TRAFICO_TASADO_{$this->userIdentifier}
                        GROUP BY PLAN, SERVICIO_DES
                    ) FETCH FIRST 1 ROWS ONLY;
                END IF;

                INSERT INTO USRAES.TRAFICO_TASADO_{$this->userIdentifier}(FCH_TRAFICO, MSISDN, PLAN, SERVICIO_DES, TRAFICO_BYTES)
                SELECT to_date('{$strDay}', 'yyyymmdd') FCH_TRAFICO, a.MSISDN,
                V_PLAN PLAN,
                V_SERVICIO_DES SERVICIO_DES,
                0 TRAFICO_BYTES
                FROM (
                    select SUBSCRIPTION_ACCESS_NUMBER MSISDN from  USRAES.LINEAS_DE_LAS_CUENTAS_{$this->userIdentifier}
                    group by SUBSCRIPTION_ACCESS_NUMBER
                ) a
                WHERE a.MSISDN not in (
                    SELECT MSISDN FROM USRAES.TRAFICO_TASADO_{$this->userIdentifier} GROUP BY MSISDN
                );
                COMMIT;
            END;"
        ];

        $this->exec_sql($queries);
        
        return DB::select(DB::raw("SELECT '{$strDates}' fch_trafico, msisdn, plan,SERVICIO_DES, sum(trafico_bytes) trafico_bytes
        FROM USRAES.TRAFICO_TASADO_{$this->userIdentifier}
        GROUP BY msisdn,plan,servicio_des"));
    }

    private function exec_sql(array $queries)
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
