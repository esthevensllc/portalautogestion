<?php

namespace AMovil\Reports\ExtraccionDevolucion\TablaInteres\Services;

use AMovil\Reports\ExtraccionDevolucion\TablaInteres\Domain\TablaInteresRepository;
use AMovil\Shared\Application\Response;
use DateTime;

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
        $this->repo->insert($dtFecha, $tasa, $factorDiario, $factorAcumulado);
        return new Response([],[]);
    }
}
