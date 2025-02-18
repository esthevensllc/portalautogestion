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

        $queryFilter = null;
        if($field === "msisdn"){
            $queryFilter = "T.MSISDN IN (SELECT value FROM USRAES.DAPU_LINEA_IMEI_INPUT_{$this->userIdentifier})";
        } else {
            $queryFilter = "T.IMEI IN (SELECT value FROM USRAES.DAPU_LINEA_IMEI_INPUT_{$this->userIdentifier})";
        }

        $query = "SELECT
            MSISDN, IMSI, IMEI, FEC_ULTIMO_TRAFICO, RN
        FROM (
            SELECT T.MSISDN, T.IMSI, SUBSTR(T.IMEI, 0, 14) AS IMEI, T.FEC_ULTIMO_TRAFICO,
                ROW_NUMBER() OVER (PARTITION BY T.MSISDN ORDER BY T.FEC_ULTIMO_TRAFICO DESC) AS RN
            FROM (
                SELECT /*+ PARALLEL(20) */ T.MSISDN, T.IMSI, T.IMEI, T.FEC_ULTIMO_TRAFICO
                FROM DWA.F_M_TRIPLETA T WHERE {$queryFilter}
                UNION ALL
                SELECT /*+ PARALLEL(20) */ T.MSISDN, T.IMSI, T.IMEI, T.FEC_ULTIMO_TRAFICO
                FROM DWA.F_M_TRIPLETA_GPRS T WHERE {$queryFilter}
            ) T
        ) WHERE RN = 1";

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
