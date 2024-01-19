<?php

namespace AMovil\Shared\Infrastructure\Repository;

use ClickHouseDB\Client;
use InvalidArgumentException;

class ClickhouseDB
{
    private $connections = [];

    public function connection($connectionId)
    {
        if(array_key_exists($connectionId, $this->connections)){
            return $this->connections[$connectionId];
        }
        $config = config("database.connections.{$connectionId}");
        if($config === null){
            throw new InvalidArgumentException("La conexión {$connectionId} no existe");
        }
        $db = new Client([
            'host' => $config['host'],
            'port' => $config['port'],
            'username' => $config['username'],
            'password' => $config['password'],
            // 'https' => true
        ]);
        $db->database($config["database"]);
        $db->setTimeout(60);
        $db->setConnectTimeOut(5);
        $db->ping(true);
        return $this->connections[$connectionId] = $db;
    }
}
