<?php

namespace AMovil\Reports\ReporteLineasEnrutadas\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ReporteLineasEnrutadas\Domain\ReporteLineasEnrutadasRepository;
use AMovil\Shared\Infrastructure\Repository\ClickhouseDB;
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

    public function getReporte(array $macs)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        
        // Limpiar la tabla antes de insertar los nuevos datos
        $this->db->write("TRUNCATE TABLE reportes.lineas_enrutadas_1");

        $data = [];
        foreach ($macs as $item) {
            $fecha = $item['fecha'];
            $telefono = $item['telefono'];

            // Agregar los datos en el formato correcto para la inserción
            $data[] = [
                'dia' => $fecha,
                'msisdn' => $telefono
            ];
        }

        // Insertar los datos en la tabla
        $this->db->insert("reportes.lineas_enrutadas_1", $data, ['dia', 'msisdn']);

        // Ejecutar la consulta con los datos insertados
        $query = "SELECT x.day, x.calledno AS fono1, x.flag 
                FROM (
                    SELECT day, calledno, IF(d > 0, 1, 0) AS flag 
                    FROM xdrs.voice_mtdetail_subscriber_1day 
                    WHERE day >= (SELECT MIN(dia) FROM reportes.lineas_enrutadas_1) 
                        AND operador2 = 'movistar' 
                        AND toString(calledno) IN (SELECT toString(msisdn) 
                                                FROM reportes.lineas_enrutadas_1 
                                                GROUP BY 1) 
                        AND d > 0 
                ) x
                JOIN reportes.lineas_enrutadas_1 y 
                ON toString(calledno) = toString(msisdn) 
                WHERE x.day > y.dia 
                GROUP BY 1, 2, 3 
                ORDER BY 1 DESC";
        
        // Obtener los resultados de la consulta
        $data = $this->db->select($query)->rows();

        return $data;
    }
}
