<?php

namespace AMovil\Reports\BloqueoControlReg\Infrastructure;

use AMovil\Reports\BloqueoControlReg\Domain\BloqueoControlRegLogRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentBloqueoControlRegLogRepository implements BloqueoControlRegLogRepository
{
    public function saveLog($id, $tipoDocumentoId, $filename, $sizeBytes)
    {
        DB::table("usraes.bloqueo_control_regulatorio_log")->insert([
            "id" => $id,
            "fecha" => new DateTime(),
            "tipo_documento_id" => $tipoDocumentoId,
            "filename" => $filename,
            "size_bytes" => $sizeBytes,
        ]);
    }

    public function getByCriteria($filters = [])
    {
        $builder = DB::table("usraes.bloqueo_control_regulatorio_log");
        foreach($filters as $row){
            $builder->where($row[0], $row[1]);
        }
        return $builder->get();
    }
}
