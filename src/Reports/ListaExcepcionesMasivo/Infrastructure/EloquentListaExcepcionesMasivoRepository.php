<?php

namespace AMovil\Reports\ListaExcepcionesMasivo\Infrastructure;

use AMovil\Reports\ListaExcepcionesMasivo\Domain\ListaExcepcionesMasivoRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentListaExcepcionesMasivoRepository implements ListaExcepcionesMasivoRepository
{
    public function saveLog($id, $tipoOperacionId, $filename, ?int $sizeBytes, $eirFilename, $username, array $lista)
    {
        DB::transaction(function() use ($id, $tipoOperacionId, $filename, $sizeBytes, $eirFilename, $username, $lista){
            $chunk = [];
            $lastIndex = count($lista)-1;
            foreach($lista as $index => $row){
                $row["documento_id"] = $id;
                $chunk[] = $row;
                if(count($chunk) === 100 || $index === $lastIndex){
                    DB::table("usraes.lista_excepciones_masivo_log_detalle")->insert($chunk);
                    $chunk = [];
                }
            }

            $cantUnicos = DB::select("select count(*) counter from (
                select imei, imsi, linea from usraes.lista_excepciones_masivo_log_detalle
                where documento_id = :id
                group by imei, imsi, linea
            )", ["id" => $id]);
            $cantUnicos = $cantUnicos[0]->counter;

            DB::table("usraes.lista_excepciones_masivo_log")->insert([
                "id" => $id,
                "fecha" => new DateTime(),
                "filename" => $filename,
                "tipo_operacion_id" => $tipoOperacionId,
                "eir_filename" => $eirFilename,
                "size_bytes" => $sizeBytes,
                "cant_registros" => count($lista),
                "cant_unicos" => $cantUnicos,
                "processed" => 0,
                "username" => $username,
            ]);
        });
    }

    public function getByCriteria($filters = [])
    {
        $builder = DB::table("usraes.lista_excepciones_masivo_log");
        
        foreach($filters as $row){
            $builder->where($row[0], $row[1]);
        }

        return $builder->get();
    }

    public function getTiposOperacion()
    {
        $result = [
            ["id" => 1, "label" => "INGRESAR LISTA"],
            ["id" => 2, "label" => "RETIRAR LISTA"],
        ];
        return json_decode(json_encode($result), false);
    }

    public function getEirReponseByEirId($eirId)
    {
        return DB::table("usraes.lista_excepciones_masivo_eir_resp")
        ->where("eir_id", $eirId)
        ->get();
    }
}
