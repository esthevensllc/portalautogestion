<?php

namespace AMovil\Reports\BloqueoControlReg\Infrastructure;

use AMovil\Reports\BloqueoControlReg\Domain\BloqueoControlRegLogRepository;
use DateTime;
use Illuminate\Support\Facades\DB;
use PDO;

class EloquentBloqueoControlRegLogRepository implements BloqueoControlRegLogRepository
{
    public function saveLog($id, $tipoOperacionId, $tipoDocumentoId, $filename, $sizeBytes, $filePath, $eirFilename, $cantRegistros, $cantUnicos)
    {
        if($filePath !== null){
            DB::transaction(function($conn) use ($id, $tipoOperacionId, $tipoDocumentoId,
            $filename, $sizeBytes, $filePath, $eirFilename, $cantRegistros, $cantUnicos){
                $now = new DateTime();
                $strNow = $now->format("Y-m-d H:i:s");
                $pdo = $conn->getPdo();
                $sql = "INSERT INTO usraes.bloqueo_control_regulatorio_log (
                    id, fecha, tipo_operacion_id, tipo_documento_id, filename, size_bytes, eir_filename, cant_registros, cant_unicos, filecontent
                )
                VALUES (:id, to_date(:fecha, 'yyyy-mm-dd hh24:mi:ss'), :tipo_operacion_id, :tipo_documento_id,
                :filename, :size_bytes, :eir_filename, :cant_registros, :cant_unicos, EMPTY_BLOB())
                RETURNING filecontent INTO :blob";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id', $id, PDO::PARAM_STR);
                $stmt->bindParam(':fecha', $strNow, PDO::PARAM_STR);
                $stmt->bindParam(':tipo_operacion_id', $tipoOperacionId, PDO::PARAM_STR);
                $stmt->bindParam(':tipo_documento_id', $tipoDocumentoId, PDO::PARAM_STR);
                $stmt->bindParam(':filename', $filename, PDO::PARAM_STR);
                $stmt->bindParam(':size_bytes', $sizeBytes, PDO::PARAM_STR);
                $stmt->bindParam(':eir_filename', $eirFilename, PDO::PARAM_STR);
                $stmt->bindParam(':cant_registros', $cantRegistros, PDO::PARAM_STR);
                $stmt->bindParam(':cant_unicos', $cantUnicos, PDO::PARAM_STR);
                $stmt->bindParam(':blob', $lob, PDO::PARAM_LOB);
                $stmt->execute();
                $lob->save(base64_encode(file_get_contents($filePath)));
            });
        }else{
            DB::table("usraes.bloqueo_control_regulatorio_log")->insert([
                "id" => $id,
                "fecha" => new DateTime(),
                "tipo_operacion_id" => $tipoOperacionId,
                "tipo_documento_id" => $tipoDocumentoId,
                "filename" => $filename,
                "size_bytes" => $sizeBytes,
                "eir_filename" => $eirFilename,
                "cant_registros" => $cantRegistros,
                "cant_unicos" => $cantUnicos,
            ]);
        }
    }

    public function getByCriteria($filters = [])
    {
        $builder = DB::table("usraes.bloqueo_control_regulatorio_log");
        $builder->select("id", "fecha", "tipo_operacion_id", "tipo_documento_id", "filename", "size_bytes", "eir_filename", "cant_registros", "cant_unicos", "exec_ok", "exec_fail", "processed");
        foreach($filters as $row){
            $builder->where($row[0], $row[1]);
        }
        return $builder->get();
    }

    public function findFileContentById($id)
    {
        return DB::table("usraes.bloqueo_control_regulatorio_log")
        ->where("id", $id)
        ->first();
    }

    public function getTiposOperacion()
    {
        $result = [
            ["id" => 1, "label" => "BLOQUEO"],
            ["id" => 2, "label" => "DESBLOQUEO"],
        ];
        return json_decode(json_encode($result), false);
    }

    public function getTiposDocumento()
    {
        $result = [
            ["id" => 1, "label" => "SIBMED"],
            ["id" => 2, "label" => "DAPU"],
        ];
        return json_decode(json_encode($result), false);
    }
}
