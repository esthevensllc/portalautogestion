<?php

namespace AMovil\Reports\OltCmts\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\OltCmts\Domain\OltCmtsRepository;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class EloquentOltCmtsRepository implements OltCmtsRepository
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
            // ["id" => "2", "label" => "CMTS"],
        ];
        return json_decode(json_encode($data), false);
    }

    public function getOltsValues(DateTime $fecha)
    {
        $stFecha = $fecha->format("Y-m-d");
        $query = "SELECT nombre_olt id, nombre_olt label FROM portal_autogestion.list_olt_1day
        where fecha = toDate('{$stFecha}') and nombre_olt is not null
        order by nombre_olt";
        $data = DB::connection("ch-dn05")->select(DB::raw($query));
        return json_decode(json_encode($data), false);
    }

    public function getCmtsValues(DateTime $fecha)
    {
        $stFecha = $fecha->format("Y-m-d");
        $query = "SELECT ubicacion_de_red id, ubicacion_de_red label FROM portal_autogestion.list_olt_cmts_hfc_1day
        where fecha = toDate('{$stFecha}') and ubicacion_de_red is not null
        order by ubicacion_de_red";
        $data = DB::connection("ch-dn05")->select(DB::raw($query));
        return json_decode(json_encode($data), false);
    }

    public function getOltReport(DateTime $fecha, array $olts)
    {
        $now = new DateTime();
        $diff = $fecha->diff($now);
        // $strValues = implode("','", $olts);
        $this->userIdentifier = $this->authService->getUserIdentifier();
        

        $request = [
            "type_id" => "1",
            "days" => "{$diff->days}",
            "values" => $olts
        ];
        $http_response = Http::withHeaders([
            "x-user-identifier" => $this->userIdentifier,
            // "Content-Type" => "application/json"
        ])
        ->timeout(-1)
        ->post(env("APP_API")."/olt-cmts/process", $request);

        if($http_response->getStatusCode() !== 200){
            $error_message = $http_response->body();
            throw new Exception(json_encode($error_message));
        }
        return DB::connection("oracle")->select("select * from usraes.olt_mac_final_{$this->userIdentifier}");
    }

    public function getOltListSummary()
    {
        $query = "SELECT min(fecha) fecha_min, max(fecha) fecha_max FROM portal_autogestion.list_olt_1day";
        $result = DB::connection("ch-dn05")->select($query);
        return json_decode(json_encode($result[0]), false);
    }
}
