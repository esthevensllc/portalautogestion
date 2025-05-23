<?php

namespace AMovil\Reports\ExtraccionDevolucion\TablaInteres\Services;

use AMovil\Reports\ExtraccionDevolucion\TablaInteres\Domain\TablaInteresRepository;
use AMovil\Shared\Application\Response;
use DateTime;
use Exception;

class RegisterTasaInteres
{
    private $repo;
    
    public function __construct(TablaInteresRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($fecha, $tasa, $factorDiario, $factorAcumulado)
    {
        $dtFecha = DateTime::createFromFormat("Y-m-d", $fecha);
        if ($this->repo->existsIn($dtFecha)) {
            return new Response(["message" => "La fecha {$fecha} ya existe en la tabla de interes"]);
        }
        $this->repo->insert($dtFecha, $tasa, $factorDiario, $factorAcumulado);
        return new Response([],[]);
    }
}
