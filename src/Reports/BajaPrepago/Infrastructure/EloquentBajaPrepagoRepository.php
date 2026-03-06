<?php

namespace AMovil\Reports\BajaPrepago\Infrastructure;

use AMovil\Reports\BajaPrepago\Domain\BajaPrepagoRepository;
use AMovil\Reports\BajaPrepago\Domain\BajaPrepagoStatus;
use DateTime;
use Generator;
use Illuminate\Support\Facades\DB;

class EloquentBajaPrepagoRepository implements BajaPrepagoRepository
{
    public function insertPreimport(Generator $msisdnList) {
        $chunk = [];
        foreach($msisdnList as $row){
            $chunk[] = $row;
            if(count($chunk) === 20){
                DB::table("BAJAS_PREPAGO_OSIPTEL_PRE_IMPORT")->insert($chunk);
                $chunk = [];
            }
        }
        if(count($chunk) > 0){
            DB::table("BAJAS_PREPAGO_OSIPTEL_PRE_IMPORT")->insert($chunk);
            $chunk = [];
        }
    }

    public function validatePreimport($baseId) {
        $strNow = new DateTime();
        $strNow->modify("-1 day");
        $strNow = $strNow->format("Ymd");

        DB::statement("MERGE INTO BAJAS_PREPAGO_OSIPTEL_PRE_IMPORT A
        USING (
            SELECT
            x.MSISDN,
            CASE
                WHEN Z.MSISDN IS NOT NULL THEN 'EN PROCESO'
                WHEN HLR_INDEX=1 AND charge_globa='HOT' AND ROUTECATEGORY IN (6,46) THEN 'VALIDADO'
                ELSE 'NO VALIDADO' END
                AS FLAG
            FROM USRAES.BAJAS_PREPAGO_OSIPTEL_PRE_IMPORT X
            LEFT JOIN DWS.SA_HLR_UDB PARTITION (P_{$strNow}) Y
                ON X.MSISDN=Y.MSISDN
            LEFT JOIN USRAES.BAJAS_PREPAGO_OSIPTEL Z
                ON X.MSISDN=Z.MSISDN
            WHERE X.BASE_ID = :p_base_id
            GROUP BY
            x.MSISDN,
            CASE
            WHEN Z.MSISDN IS NOT NULL THEN 'EN PROCESO'
            WHEN HLR_INDEX=1 AND charge_globa='HOT' AND ROUTECATEGORY IN (6,46) THEN 'VALIDADO'
            ELSE 'NO VALIDADO' END
        ) B
        ON (A.MSISDN = B.MSISDN)
        WHEN MATCHED THEN UPDATE SET
        A.flag_validacion = B.FLAG
        WHERE A.BASE_ID = :p_base_id", ["p_base_id" => $baseId]);
    }

    public function getPreimportSummary($baseId) {
        return DB::table("BAJAS_PREPAGO_OSIPTEL_PRE_IMPORT")
        ->selectRaw("CLASE, SUBCLASE, NOTAS, USERNAME, FLAG_VALIDACION, count(*) cantidad")
        ->where("base_id", $baseId)
        ->groupBy("clase", "subclase", "notas", "username", "flag_validacion")
        ->get();
    }

    public function insertFromPreimport($baseId) {
        DB::statement("INSERT INTO BAJAS_PREPAGO_OSIPTEL(BASE_ID, MSISDN, FECHA_CARGA, CLASE, SUBCLASE, NOTAS)
        SELECT BASE_ID, MSISDN, FECHA_CARGA, CLASE, SUBCLASE, NOTAS
        FROM BAJAS_PREPAGO_OSIPTEL_PRE_IMPORT
        WHERE BASE_ID = :P_BASE_ID
        AND FLAG_VALIDACION = :p_flag
        ", ["p_base_id" => $baseId, 'p_flag' => BajaPrepagoStatus::VALIDADO]);
    }

    public function createLog($baseId, $clase, $subClase, $notas, $username, $cantLineas, $ipAdress) {
        DB::table("BAJAS_PREPAGO_OSIPTEL_LOG")->insert([
            "base_id" => $baseId,
            "clase" => $clase,
            "subclase" => $subClase,
            "notas" => $notas,
            "username" => $username,
            "cant_lineas" => $cantLineas,
            "fecha_carga" => new DateTime(),
            "ip_address" => $ipAdress,
        ]);
    }

    public function findLogByBaseId($baseId) {
        return DB::table("BAJAS_PREPAGO_OSIPTEL_LOG")
        ->select("base_id", "clase", "subclase", "notas", "username", "cant_lineas", "fecha_Carga", "ip_address")
        ->where("base_id", $baseId)
        ->first();
    }

    public function getPreimportByBaseId($baseId, $estado, $username) {
        return DB::table("BAJAS_PREPAGO_OSIPTEL_PRE_IMPORT")
        ->selectRaw("MSISDN, CLASE, SUBCLASE, NOTAS, USERNAME, FLAG_VALIDACION, USERNAME")
        ->where("base_id", $baseId)
        ->where("flag_validacion", $estado)
        ->where("username", $username)
        ->cursor();
    }

    public function getLogByCriteria($criteria)
    {
        return DB::table("BAJAS_PREPAGO_OSIPTEL_LOG")
        ->select("base_id", "clase", "subclase", "notas", "username", "cant_lineas", "fecha_Carga", "ip_address")
        ->get();
    }

    public function getClaseList() {

    }

    public function getSubClaseList() {

    }
    
    public function getNotasList() {

    }
}
