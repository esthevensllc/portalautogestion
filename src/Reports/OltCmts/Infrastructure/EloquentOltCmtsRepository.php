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
            ["id" => "2", "label" => "CMTS"],
        ];
        return json_decode(json_encode($data), false);
    }

    public function getOltsValues(DateTime $fecha)
    {
        $stFecha = $fecha->format("Y-m-d");
        $query = "SELECT device_name id, device_name label
        FROM fija_analisis.fija_usuarios_ftth
        WHERE tecnologia = 'FTTH' and fecha = toDate('{$stFecha}') and device_name is not null
        group by 1
        order by device_name";
        $data = DB::connection("ch-dn09")->select(DB::raw($query));
        return json_decode(json_encode($data), false);
    }

    public function getCmtsValues(DateTime $fecha)
    {
        $stFecha = $fecha->format("Y-m-d");
        $query = "SELECT
        device_name id, device_name label
        from fija_analisis.fija_usuarios_hfc
        where tecnologia='HFC' and fecha = toDate('{$stFecha}') and device_name is not null
        group by 1
        order by device_name";
        $data = DB::connection("ch-dn09")->select(DB::raw($query));
        return json_decode(json_encode($data), false);
    }

    public function getOltReport(DateTime $fecha, array $olts)
    {
        $this->generateReport(1, $fecha, $olts);
        return DB::connection("oracle")->select(DB::raw("SELECT 
        CUSTOMER_ID_CODCLI,
        FUENTE,
        SERIALNUMBER,
        NOMBRE,
        ID_CARD_TYPE_VALUE,
        TELEFONO_CLARO,
        NUMERO_ADICIONAL,
        SERVICIO_PRODUCTO,
        CORREO,
        DISTRITO,
        PROVINCIA,
        DEPARTAMENTO,
        DESCRIPCION_PRODUCTO,
        AGREEMENT_STATUS,
        AGREEMENT_STATUS_DATE,
        AGREEMENT_START_DATE,
        AGREEMENT_END_DATE,
        INSTALLATION_MAP,
        NUMERO, 
        CASE
        WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
        WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
        WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
        ELSE DNI_RUC
        END DNI_RUC FROM USRAES.OLT_MAC_FINAL_{$this->userIdentifier}"));
    }

    public function getCmtsReport(DateTime $fecha, array $cmts)
    {
        $this->generateReport(2, $fecha, $cmts);
        return DB::connection("oracle")->select(DB::raw("SELECT 
        CUSTOMER_ID_CODCLI,
        FUENTE,
        SERIALNUMBER,
        NOMBRE,
        ID_CARD_TYPE_VALUE,
        TELEFONO_CLARO,
        NUMERO_ADICIONAL,
        SERVICIO_PRODUCTO,
        CORREO,
        DISTRITO,
        PROVINCIA,
        DEPARTAMENTO,
        DESCRIPCION_PRODUCTO,
        AGREEMENT_STATUS,
        AGREEMENT_STATUS_DATE,
        AGREEMENT_START_DATE,
        AGREEMENT_END_DATE,
        INSTALLATION_MAP,
        NUMERO, 
        CASE
        WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
        WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
        WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
        ELSE DNI_RUC
        END DNI_RUC FROM USRAES.cmts_table_final_{$this->userIdentifier}"));
    }

    private function generateReport(int $type_id, DateTime $fecha, array $values)
    {
        $now = new DateTime();
        $diff = $fecha->diff($now);
        // $strValues = implode("','", $olts);
        $this->userIdentifier = $this->authService->getUserIdentifier();
        

        $request = [
            "type_id" => $type_id,
            "days" => "{$diff->days}",
            "values" => $values
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
    }

    public function getOltListSummary()
    {
        $query = "SELECT min(fecha) fecha_min, max(fecha) fecha_max FROM portal_autogestion.list_olt_1day";
        $result = DB::connection("ch-dn05")->select($query);
        return json_decode(json_encode($result[0]), false);
    }
}
