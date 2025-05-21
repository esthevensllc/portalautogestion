<?php

namespace AMovil\Reports\ExtraccionDevFija\InformesFalla\Infrastructure;

use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFallasRepository;
use AMovil\Shared\Infrastructure\Eloquent\EloquentCriteriaConverter;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentInformeFallasRepository implements InformeFallasRepository
{
    private $connection = "oracle";

    private $fields = [
        "numero_reporte" => ["label" => 'numero_reporte', "type" => 'string'],
        "servicio_afectado_id" => ["label" => 'servicio_afectado_id', "type" => 'string'],
        "name_file" => ["label" => 'name_file', "type" => 'string'],
        "ticket" => ["label" => 'Ticket', "type" => 'string'],
        "username" => ["label" => 'username', "type" => 'string'],
        "fecha_carga" => ["label" => 'fecha_carga', "type" => 'datetime'],
        "revisado" => ["label" => 'revisado', "type" => 'number'],
        "aprobado" => ["label" => 'aprobado', "type" => 'number'],
        "procesado" => ["label" => 'procesado', "type" => 'number'],
        "en_ejecucion" => ["label" => 'en_ejecucion', "type" => 'number'],
        "acreditado" => ["label" => 'acreditado', "type" => 'number'],
    ];
    
    public function createInformeFallas(string $numReporte, string $filename, string $username, array $servicios)
    {
        $now = new DateTime();
        foreach($servicios as $row){
            DB::connection($this->connection)
            ->table("usraes.noc_informe_de_fallas_fija")
            ->insert([
                "numero_reporte" => $numReporte,
                "name_file" => $filename,
                "username" => $username,
                "servicio_afectado_id" => $row["servicioAfectadoId"],
                "compensacion_id" => $row["compensacionId"],
                "fecha_carga" => $now,
                "revisado" => 0,
                "aprobado" => 0,
                "procesado" => 0,
                "en_ejecucion" => 0,
                "acreditado" => 0,
            ]);
        }
    }

    public function getByCriteria(array $filters, $sortBy = [], $offset=0, $limit=0)
    {
        $builder = DB::table('usraes.noc_informe_de_fallas_fija');
        EloquentCriteriaConverter::fromRawArray($builder, $this->fields, $filters, $sortBy);

        $response = [
            'recordsTotal' => DB::table('usraes.noc_informe_de_fallas_fija')->count(),
            'recordsFiltered' => $builder->count(),
            'data' => []
        ];

        $builder = DB::table('usraes.noc_informe_de_fallas_fija');
        EloquentCriteriaConverter::fromRawArray($builder, $this->fields, $filters, $sortBy, $offset, $limit);

        $response['data'] = $builder->get();
        return $response;
    }

    public function updateStatusToRevisado(string $numReporte, int $servicioAfectadoId) {
        DB::table('usraes.noc_informe_de_fallas_fija')
        ->where("numero_reporte", $numReporte)
        ->where("servicio_afectado_id", $servicioAfectadoId)
        ->update(["revisado" => 1]);
    }

    public function updateStatusToAprobado(string $numReporte, int $servicioAfectadoId, string $ticket) {
        DB::table('usraes.noc_informe_de_fallas_fija')
        ->where("numero_reporte", $numReporte)
        ->where("servicio_afectado_id", $servicioAfectadoId)
        ->update(["aprobado" => 1, 'ticket' => $ticket]);
    }
    
    public function updateStatusToDesaprobado(string $numReporte, int $servicioAfectadoId) {
        DB::table('usraes.noc_informe_de_fallas_fija')
        ->where("numero_reporte", $numReporte)
        ->where("servicio_afectado_id", $servicioAfectadoId)
        ->update(["aprobado" => 2, 'ticket' => null]);
    }
    
    public function updateStatusToEnEspera(string $numReporte, int $servicioAfectadoId) {
        DB::table("usraes.noc_informe_de_fallas_fija")
        ->where('numero_reporte', $numReporte)
        ->where("servicio_afectado_id", $servicioAfectadoId)
        ->update(['aprobado' => 0, 'ticket' => null]);
    }
    
    public function updateStatusToEnEjecucion(string $numReporte, int $servicioAfectadoId) {
        DB::table('usraes.noc_informe_de_fallas_fija')
        ->where("numero_reporte", $numReporte)
        ->where("servicio_afectado_id", $servicioAfectadoId)
        ->update(["en_ejecucion" => 1]);
    }
    
    public function updateStatusToEnEsperaEjecucion(string $numReporte, int $servicioAfectadoId) {
        DB::table('usraes.noc_informe_de_fallas_fija')
        ->where("numero_reporte", $numReporte)
        ->where("servicio_afectado_id", $servicioAfectadoId)
        ->update(["en_ejecucion" => 0]);
    }

    public function updateStatusToProcesado(string $numReporte, int $servicioAfectadoId)
    {
        DB::table('usraes.noc_informe_de_fallas_fija')
        ->where("numero_reporte", $numReporte)
        ->where("servicio_afectado_id", $servicioAfectadoId)
        ->update(["procesado" => 1]);
    }

    public function updateStatusToSinProcesar(string $numReporte, int $servicioAfectadoId)
    {
        DB::table('usraes.noc_informe_de_fallas_fija')
        ->where("numero_reporte", $numReporte)
        ->where("servicio_afectado_id", $servicioAfectadoId)
        ->update(["procesado" => 0]);
    }
    
    public function delete(string $numReporte, int $servicioAfectadoId)
    {
        DB::table('usraes.noc_informe_de_fallas_fija')
        ->where("numero_reporte", $numReporte)
        ->where("servicio_afectado_id", $servicioAfectadoId)
        ->delete();
    }

    public function getServiciosAfectados()
    {
        return DB::table("usraes.INPUT_DEVO_FIJA_SERVICIOS_AFECTADOS")->get();
    }

    public function findServicioAfectado(int $servicioAfectadoId)
    {
        return DB::table("usraes.INPUT_DEVO_FIJA_SERVICIOS_AFECTADOS")
        ->where("id", $servicioAfectadoId)
        ->first();
    }
}
