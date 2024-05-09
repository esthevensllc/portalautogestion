<?php

namespace AMovil\Reports\ListaExcepcionesEliminar\Services;

use AMovil\Reports\ListaExcepcionesEliminar\Domain\ListaExcepcionesEliminarRepository;

class ListaExcepcionesEliminarFinder
{
    private $repo;

    public function __construct(ListaExcepcionesEliminarRepository $repo)
    {
        $this->repo = $repo;
    }

    public function findLast()
    {
        return $this->repo->findLast();
    }

    public function findByImei($imei)
    {
        return $this->repo->getByImei($imei);
    }
}
