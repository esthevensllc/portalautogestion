<?php

namespace AMovil\Reports\SolicitudDatosConfirmacion\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\SolicitudDatosConfirmacion\Domain\SolicitudDatosConfirmacionRepository;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class EloquentSolicitudDatosConfirmacionRepository implements SolicitudDatosConfirmacionRepository
{
    private $connection = "oracle";
    private $userIdentifier;
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function getTable($filter1,$filter2,$filter3,$filter4){

        $this->userIdentifier = $this->authService->getUserIdentifier();

        $CODIGO = $this->userIdentifier;

        if($filter1 == 0){
            $MSISDN = explode(',',$filter2);
            $MSISDN = "'" . implode("','", array_map('strval', $MSISDN)) ."'";
            $where = "subscription_access_number in ({$MSISDN})";
        }
        if($filter1 == 1){
            $where = "to_number(regexp_replace(ID_CARD_VALUE, '[^0-9]+', '')) in ({$filter3})";
        }
        if($filter1 == 2){
            $where = "to_number(regexp_replace(ID_CARD_VALUE, '[^0-9]+', ''))= {$filter3} AND subscription_access_number = '{$filter2}'";
        }

        $select = "";
        $select2 = "";
        $collection = Collection::make($filter4);

        foreach($filter4 as $key => $val){
            if($val == 'id_card_value'){
                $select .= "id_card_value NUMERO_DE_DOCUMENTO";
                $select2 .= "NUMERO_DE_DOCUMENTO";
            }
            if($val == 'id_card_type_value'){
                $select .= "upper(id_card_type_value) TIPO_DE_DOCUMENTO";
                $select2 .= "TIPO_DE_DOCUMENTO";
            }
            if($val == 'customer_full_name'){
                $select .= "upper(customer_full_name) NOMBRE_CLIENTE";
                $select2 .= "NOMBRE_CLIENTE";
            }
            if($val == 'agreement_mode'){
                $select .= "upper(agreement_mode) MODALIDAD_DEL_SERVICIO";
                $select2 .= "MODALIDAD_DEL_SERVICIO";
            }
            if($val == 'subscription_access_number'){
                $select .= "subscription_access_number NUMERO_CONTRATADO";
                $select2 .= "NUMERO_CONTRATADO";
            }
            if($val == 'agreement_status'){
                $select .= "agreement_status ESTADO_DEL_SERVICIO";
                $select2 .= "ESTADO_DEL_SERVICIO";
            }
            if($val == 'subscription_start_date'){
                $select .= "subscription_start_date FECHA_ALTA";
                $select2 .= "FECHA_ALTA";
            }
            if($val == 'subscription_end_date'){
                $select .= "case 
                        when agreement_status='D' and to_char(subscription_end_date,'yyyy-mm-dd')='9999-12-31' 
                        then subscription_status_date else subscription_end_date end FECHA_BAJA";
                $select2 .= "FECHA_BAJA";
            }
            if($val == 'subscription_status_date'){
                $select .= "subscription_status_date FECHA_ULTIMO_CAMBIO_ESTADO";
                $select2 .= "FECHA_ULTIMO_CAMBIO_ESTADO";
            }
            if($collection->last() != $val){
                $select .= ",";
                $select2 .= ",";
            }
        }

        $queries = [];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TEMP_VISTA_{$CODIGO}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];

        $queries[] = ["sql" => "CREATE TABLE USRAES.TEMP_VISTA_{$CODIGO} AS 
                        SELECT {$select} 
                        FROM DWA.DW_M_SUBSCRIPTION
                        where {$where}"];

        $queries[] = ["sql" => "BEGIN
                        INSERT INTO USRAES.REGISTROS_POR_USER_HIST(FECHA,CODIGO,{$select2})
                        SELECT SYSDATE FECHA,'{$CODIGO}',A.*
                        FROM USRAES.TEMP_VISTA_{$CODIGO} A;
                        COMMIT;
                    END;"];

        $this->exec_sql($queries);

        return DB::table("USRAES.TEMP_VISTA_{$CODIGO}")
        ->select("*")
        ->get();
    }

    public function export($filter1,$filter2,$filter3,$filter4){
        $data = $this->getTable($filter1,$filter2,$filter3,$filter4);
        return $data;
    }

    private function exec_sql(array $queries)
    {
        foreach($queries as $row){
            if(array_key_exists('params', $row)){
                DB::connection($this->connection)->statement(DB::Raw($row['sql']), $row['params']);
            }else{
                DB::connection($this->connection)->statement(DB::Raw($row['sql']));
            }
        }
    }
}
