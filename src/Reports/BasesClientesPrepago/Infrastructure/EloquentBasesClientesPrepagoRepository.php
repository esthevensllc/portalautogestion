<?php

namespace AMovil\Reports\BasesClientesPrepago\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\BasesClientesPrepago\Domain\BasesClientesPrepagoRepository;
use DateTime;
use Illuminate\Support\Facades\DB;
use ClickHouseDB\Client;
use Illuminate\Support\Collection;

class EloquentBasesClientesPrepagoRepository implements BasesClientesPrepagoRepository
{
    private $connection = "oracle";
    private $userIdentifier;
    private $authService;
    private $db;

    public function __construct(AuthService $authService)
    {
        try {
            $connectionConfig = [
                "host" => "172.19.242.59",
                "port" => "8123",
                "username" => "nifi",
                "password" => "nifi",
                "charset" => "utf8mb4",
                "collation" => "utf8mb4_unicode_ci"
            ];
            $this->db = new Client($connectionConfig);
        }
        catch (ClickHouseDB\Exception\QueryException $E) {
            echo "ERROR:" . $E->getMessage() . "\nOK\n";
        }
        $this->authService = $authService;
    }

    public function getDep()
    {

        $result = $this->db->select("select departamento from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx
        where y=(select max(y) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx) and  
        semana=(select max(semana) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx where y=(select max(y) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx)) group by departamento order by departamento asc"); 

        $departamentos = $result->rows();

        return $departamentos;
    }

    public function getProv($dep)
    {

        $result = $this->db->select("select provincia from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx
        where y=(select max(y) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx) and  
        semana=(select max(semana) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx where  y=(select max(y) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx)) and departamento = '$dep' group by provincia order by provincia asc"); 

        $provincias = $result->rows();

        return $provincias;
    }

    public function getDist($dep,$prov)
    {

        $result = $this->db->select("select distrito from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx
        where y=(select max(y) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx) and  
        semana=(select max(semana) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx where  y=(select max(y) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx)) and departamento = '$dep' and provincia= '$prov' group by distrito order by distrito asc"); 

        $distritos = $result->rows();

        return $distritos;
    }

    public function getReporte($granu, $dep, $prov, $dist)
    {   
        if($granu == "0"){
            $result = $this->db->select("select fono from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx
            where y=(select max(y) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx) and  
            semana=(select max(semana) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx where  y=(select max(y) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx))
            and toString(departamento)='$dep'
            and toString(tplname)='Prepago'");

            $data = $result->rows();

            return $data;
        }     
        if($granu == "1"){
            $result = $this->db->select("select fono from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx
            where y=(select max(y) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx) and  
            semana=(select max(semana) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx where  y=(select max(y) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx))
            and toString(departamento)='$dep' and toString(provincia)='$prov'
            and toString(tplname)='Prepago'");

            $data = $result->rows();   

            return $data;
        }
        if($granu == "2"){
            $result = $this->db->select("select fono from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx
            where y=(select max(y) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx) and  
            semana=(select max(semana) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx where  y=(select max(y) from user_activity.net_subscriber_activity_weeks_hist_with_udb_rx))
            and toString(departamento)='$dep' and toString(provincia)='$prov' and toString(distrito)='$dist'
            and toString(tplname)='Prepago'");

            $data = $result->rows();   

            return $data;
        }
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
