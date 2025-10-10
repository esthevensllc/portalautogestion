<?php

namespace AMovil\Reports\DAPU\UltimoTrafico\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\DAPU\UltimoTrafico\Domain\UltimoTraficoRepository;
use Illuminate\Support\Facades\DB;

class EloquentUltimoTraficoRepository implements UltimoTraficoRepository
{
    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    
    public function getByMsisdn(array $values) {
        return $this->getBy("msisdn", $values);
    }

    public function getByImeis(array $values) {
        return $this->getBy("imei", $values);
    }

    public function getBy(string $field, array $values)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.DAPU_LINEA_IMEI_INPUT_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");
        DB::statement("CREATE TABLE USRAES.DAPU_LINEA_IMEI_INPUT_{$this->userIdentifier}(value varchar2(100))");

        foreach($values as $value){
            DB::table("USRAES.DAPU_LINEA_IMEI_INPUT_{$this->userIdentifier}")->insert(["value" => $value]);
        }

        // $queryFilter = null;
        if($field === "msisdn"){
            $query = "SELECT MSISDN, IMSI, null IMEI, FEC_ULTIMO_TRAFICO, FUENTE, RN FROM (
                SELECT  /*+ PARALLEL(8) */ 
                    T.MSISDN,
                    T.IMSI,
                    T.FEC_ULTIMO_TRAFICO,
                    CASE 
                        WHEN T.SOURCE_TABLE = 'F_M_TRIPLETA' THEN 'VOZ'
                        WHEN T.SOURCE_TABLE = 'F_M_TRIPLETA_GPRS' THEN 'DATOS'
                    END AS FUENTE,
                    ROW_NUMBER() OVER (PARTITION BY T.MSISDN ORDER BY T.FEC_ULTIMO_TRAFICO DESC) AS RN
                FROM (
                    SELECT /*+ PARALLEL(8) */ 
                        MSISDN,
                        IMSI,
                        FEC_ULTIMO_TRAFICO,
                        'F_M_TRIPLETA' AS SOURCE_TABLE
                    FROM DWA.F_M_TRIPLETA
                    WHERE MSISDN IN (SELECT value FROM USRAES.DAPU_LINEA_IMEI_INPUT_{$this->userIdentifier})

                    UNION ALL

                    SELECT /*+ PARALLEL(8) */ 
                        MSISDN,
                        IMSI,
                        FEC_ULTIMO_TRAFICO,
                        'F_M_TRIPLETA_GPRS' AS SOURCE_TABLE
                    FROM DWA.F_M_TRIPLETA_GPRS
                    WHERE MSISDN IN (SELECT value FROM USRAES.DAPU_LINEA_IMEI_INPUT_{$this->userIdentifier})
                ) T
            )
            WHERE RN = 1";
        } else {
            $query = "SELECT null MSISDN, IMSI, IMEI, FEC_ULTIMO_TRAFICO, FUENTE, RN FROM (
                SELECT /*+ PARALLEL(8) */  
                    T.IMEI,
                    T.IMSI,
                    T.FEC_ULTIMO_TRAFICO,
                    CASE 
                        WHEN T.SOURCE_TABLE = 'F_M_TRIPLETA' THEN 'VOZ'
                        WHEN T.SOURCE_TABLE = 'F_M_TRIPLETA_GPRS' THEN 'DATOS'
                    END AS FUENTE,
                    ROW_NUMBER() OVER (PARTITION BY T.IMEI ORDER BY T.FEC_ULTIMO_TRAFICO DESC) AS RN
                FROM (
                    SELECT /*+ PARALLEL(8) */ 
                        IMEI,
                        IMSI,
                        FEC_ULTIMO_TRAFICO,
                        'F_M_TRIPLETA' AS SOURCE_TABLE
                    FROM DWA.F_M_TRIPLETA
                    WHERE IMEI IN (SELECT value FROM USRAES.DAPU_LINEA_IMEI_INPUT_{$this->userIdentifier})

                    UNION ALL

                    SELECT /*+ PARALLEL(8) */ 
                        SUBSTR(IMEI,1,15) IMEI,
                        IMSI,
                        FEC_ULTIMO_TRAFICO,
                        'F_M_TRIPLETA_GPRS' AS SOURCE_TABLE
                    FROM DWA.F_M_TRIPLETA_GPRS
                    WHERE SUBSTR(IMEI,1,15) IN (SELECT value FROM USRAES.DAPU_LINEA_IMEI_INPUT_{$this->userIdentifier})
                ) T
            )
            WHERE RN = 1";
        }

        $data = DB::select(DB::raw($query));

        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.DAPU_LINEA_IMEI_INPUT_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");

        return $data;
    }
}
