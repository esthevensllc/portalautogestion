<?php

namespace AMovil\Reports\CargosFijosServiciosActivos\Infrastructure\Repository;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\CargosFijosServiciosActivos\Domain\DetalleRepository;
use AMovil\Reports\CargosFijosServiciosActivos\Domain\ReporteDetalle;
use AMovil\Reports\CargosFijosServiciosActivos\Domain\TipoReporte;
use DateTime;
use DB;
use Exception;
use Illuminate\Support\Facades\Http;
use Ramsey\Uuid\Uuid;
use AMovil\Shared\Infrastructure\Eloquent\EloquentCriteriaConverter;

class EloquentDetalleRepository implements DetalleRepository
{
    private $authService;
    private $userIdentifier;
    private $reporte_by;
    private $is_primarios = false;
    private $limit = ReporteDetalle::LIMIT;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    private function getTempfilename(){
        $filename = sys_get_temp_dir() ."/phpexport-". Uuid::uuid4()->toString().".json";
        return $filename;
    }

    public function getReporte(string $tipo_input, array $lineas, $excel_data, $num_doc, $num_cuenta, $sn)
    {
        switch ($tipo_input) {
            case 'tab_lineas':
                $array_str = implode(',', array_map(function($item){
                                return "'$item'";
                            }, $lineas));
                $where = "A.SUBSCRIPTION_ACCESS_NUMBER IN ($array_str) ";
                break;
            case 'tab_excel':
                $array_str = implode(',', array_map(function($item){
                                return "'$item'";
                            }, $excel_data));
                $where = "A.SUBSCRIPTION_ACCESS_NUMBER IN ($array_str) ";
                break;
            case 'tab_numero_documento':
                $array_str = implode(',', array_map(function($item){
                                return "'$item'";
                            }, $num_doc));
                $where = "A.ID_CARD_VALUE IN ($array_str) ";
                break;
            case 'tab_numero_cuenta':
                $array_str = implode(',', array_map(function($item){
                                return "'$item'";
                            }, $num_cuenta));
                $where = "A.CUSTOMER_ACCOUNT_DESC IN ($array_str) ";
                break;
            default:
                throw new Exception("Input no valido");
                break;
        }

        $now = new DateTime();
        // $strValues = implode("','", $olts);
        $this->userIdentifier = $this->authService->getUserIdentifier();
        

        $request = [
            "filter_tmp" => $where,
            "sn" => $sn
        ];

        $http_response = Http::withHeaders([
            "x-user-identifier" => $this->userIdentifier,
            // "Content-Type" => "application/json"
        ])
        ->timeout(-1)
        ->post(env("APP_API")."/cargos-fijos-servicios-activos/process", $request);

        if($http_response->getStatusCode() !== 200){
            $error_message = $http_response->body();
            throw new Exception(json_encode($error_message));
        }else{
            return $http_response->getStatusCode();
        }
    }

    public function saveLogReporteTemp(string $filename, DateTime $createAt, int $size_bytes)
    {
        //DB::table("USRAES.REP__ARCHIVO")->where("filename", $filename)->delete();
        DB::table("USRAES.reporte_log")->insert([
            "filename" => $filename,
            "created_at" => $createAt->format("Y-m-d H:i:s"),
            "size_bytes" => $size_bytes,
        ]);
    }

    private $fields = [
        'id' => ["label" => 'Id', "type" => 'number'],
        'name' => ["label" => 'name', "type" => 'string'],
        'codigo_c' => ["label" => 'Código', "type" => 'string'],
        'filename' => ["label" => 'Archivo', "type" => 'string'],
        'ini' => ["label" => 'Fecha Creación', "type" => 'datetime']
    ];

    public function getByCriteria(array $filters, $sortBy = [], $offset=0, $limit=0)
    {
        $builder = DB::table('usraes.reporte_log');
        EloquentCriteriaConverter::fromRawArray($builder, $this->fields, $filters, $sortBy);

        $response = [
            'recordsTotal' => DB::table('usraes.reporte_log')->count(),
            'recordsFiltered' => $builder->count(),
            'data' => []
        ];

        $builder = DB::table('usraes.reporte_log');
        EloquentCriteriaConverter::fromRawArray($builder, $this->fields, $filters, $sortBy, $offset, $limit);

        $response['data'] = $builder->get();
        return $response;
    }

    public function deleteLogReporteTemp()
    {
        return DB::table("USRAES.REP__ARCHIVO")->delete();
    }
}
