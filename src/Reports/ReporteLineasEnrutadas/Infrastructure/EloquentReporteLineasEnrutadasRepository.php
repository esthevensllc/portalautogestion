<?php

namespace AMovil\Reports\ReporteLineasEnrutadas\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ReporteLineasEnrutadas\Domain\ReporteLineasEnrutadasRepository;
use AMovil\Shared\Infrastructure\Repository\ClickhouseDB;
use Illuminate\Support\Facades\DB;
use DateTime;

class EloquentReporteLineasEnrutadasRepository implements ReporteLineasEnrutadasRepository
{
    private $db;
    private $authService;
    private $userIdentifier;

    public function __construct(ClickhouseDB $db, AuthService $authService)
    {
        $this->db = $db->connection("ch-dn03");
        $this->authService = $authService;
    }

    public function getReporte(array $macs, $operador)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        
        // Limpiar la tabla antes de insertar los nuevos datos
        //$this->db->write("TRUNCATE TABLE xdrs.lineas_enrutadas_tmp");

        $fecha_now = new DateTime();

        $data = [];
        foreach ($macs as $item) {
            $fecha = $item['fecha'];
            $telefono = $item['telefono'];

            // Agregar los datos en el formato correcto para la inserción
            $data[] = [
                'dia' => $fecha,
                'msisdn' => $telefono,
                'flag' => '0',
                'fecha_registro' => $fecha_now->format('Y-m-d H:i:s')
            ];
        }

        // Insertar los datos en la tabla
        $this->db->insert("xdrs.lineas_enrutadas_tmp", $data, ['dia', 'msisdn', 'flag', 'fecha_registro']);

        // Ejecutar la consulta con los datos insertados
        $query = "SELECT x.dia, x.calledno AS fono1, x.flag 
                FROM (
                    SELECT dia, calledno, IF( alert_time >= 2, 1, 0) AS flag 
                    FROM xdrs.voice_volte_mtdetail_subscriber_1day  
                    WHERE dia >= (SELECT MIN(dia) FROM xdrs.lineas_enrutadas_tmp where flag='0') 
                        AND operador2 = '{$operador}' 
                        AND toString(calledno) IN (SELECT toString(msisdn) FROM xdrs.lineas_enrutadas_tmp where flag='0' GROUP BY 1) 
                        AND alert_time >= 2 
                ) x
                JOIN (select * from xdrs.lineas_enrutadas_tmp where flag='0') y 
                ON toString(x.calledno) = toString(y.msisdn) 
                WHERE x.dia > y.dia 
                GROUP BY 1, 2, 3 
                ORDER BY 1 DESC";
        
        // Obtener los resultados de la consulta
        $data = $this->db->select($query)->rows();

        $this->db->write("alter table xdrs.lineas_enrutadas_tmp update flag='1' where flag='0'");

        return $data;
    }
}
