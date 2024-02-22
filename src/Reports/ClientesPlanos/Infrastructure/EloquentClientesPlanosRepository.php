<?php

namespace AMovil\Reports\ClientesPlanos\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ClientesPlanos\Domain\ClientesPlanosRepository;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class EloquentClientesPlanosRepository implements ClientesPlanosRepository
{
    private $userIdentifier;
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function getReportTypes()
    {
        $data = [
            ["id" => "1", "label" => "OLT"],
            ["id" => "2", "label" => "CMTS"],
        ];
        return json_decode(json_encode($data), false);
    }

    public function getOltsValues(DateTime $fecha)
    {
        $stFecha = $fecha->format("Y-m-d");
        $query = "SELECT plano id, plano label FROM portal_autogestion.list_olt_planos_1day
        where fecha = toDate('{$stFecha}') and plano is not null
        order by plano";
        $data = DB::connection("ch-dn05")->select(DB::raw($query));
        return json_decode(json_encode($data), false);
    }

    public function getCmtsValues(DateTime $fecha)
    {
        $stFecha = $fecha->format("Y-m-d");
        $query = "SELECT plano id, plano label FROM portal_autogestion.list_cmts_planos_1day
        where fecha = toDate('{$stFecha}') and plano is not null
        order by plano";
        $data = DB::connection("ch-dn05")->select(DB::raw($query));
        return json_decode(json_encode($data), false);
    }

    public function getOltReport(DateTime $fecha, array $olts)
    {
        $this->generateReport(1, $fecha, $olts);
        return DB::connection("oracle")->select("select * from USRAES.olt_planos_final_{$this->userIdentifier}");
    }

    public function getCmtsReport(DateTime $fecha, array $cmts)
    {
        $this->generateReport(2, $fecha, $cmts);
        return DB::connection("oracle")->select("select * from usraes.planos_cmts_table_final_{$this->userIdentifier}");
    }

    private function generateReport(int $type_id, DateTime $fecha, array $planos)
    {
        $now = new DateTime();
        $diff = $fecha->diff($now);
        // $strValues = implode("','", $olts);
        $this->userIdentifier = $this->authService->getUserIdentifier();
        

        $request = [
            "type_id" => $type_id,
            "days" => "{$diff->days}",
            "values" => $planos
        ];

        $http_response = Http::withHeaders([
            "x-user-identifier" => $this->userIdentifier,
            // "Content-Type" => "application/json"
        ])
        ->timeout(-1)
        ->post(env("APP_API")."/clientes-planos/process", $request);

        if($http_response->getStatusCode() !== 200){
            $error_message = $http_response->body();
            throw new Exception(json_encode($error_message));
        }
    }

    public function getOltListSummary()
    {
        $query = "SELECT min(fecha) fecha_min, max(fecha) fecha_max FROM portal_autogestion.list_olt_planos_1day";
        $result = DB::connection("ch-dn05")->select($query);
        return json_decode(json_encode($result[0]), false);
    }
}
