<?php

namespace AMovil\Reports\BloqueoControlReg\Infrastructure;

use AMovil\Reports\BloqueoControlReg\Domain\BloqueoControlRegRepository;
use DateTime;
use Illuminate\Support\Facades\DB;
use PDO;

class EloquentBloqueoControlRegRepository implements BloqueoControlRegRepository
{
    public function saveReporteSIBMED($id, array $values)
    {
        $now = new DateTime();
        foreach($values as $row){
            $row["documento_id"] = $id;
            $row["created_at"] = $now;
            DB::table("usraes.sibmed_table_control_imei")->insert($row);
        }
    }

    public function saveReporteDAPU($id, string $filePath, int $imei)
    {
        DB::transaction(function($conn) use ($id, $imei, $filePath){
            $pdo = $conn->getPdo();
            $sql = "INSERT INTO usraes.dapu_table_control_imei (documento_id, imei, filecontent)
            VALUES (:documento_id, :imei, EMPTY_BLOB())
            RETURNING filecontent INTO :blob";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':documento_id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':imei', $imei, PDO::PARAM_STR);
            $stmt->bindParam(':blob', $lob, PDO::PARAM_LOB);
            $stmt->execute();
            $lob->save(base64_encode(file_get_contents($filePath)));
        });
        // DB::table("usraes.bloqueo_control_regulatorio")->insert([
        //     "reporte_id" => $id,
        //     "filePath" => $imei,
        //     "imei" => $imei,
        // ]);
    }

    public function findReporteDAPU($id)
    {
        return DB::table("usraes.dapu_table_control_imei")
        ->where("documento_id", $id)
        ->first();
    }

    public function getReporte($id)
    {   
        $result = DB::table("usraes.bloqueo_control_reg_eir_resp")
            //->selectRaw("V1, V2, V3, V4, V5, STATUS, V7, V8, V9")
            ->where("EIR_ID", $id)
            ->get();
        
        return $result;    
    }
}
