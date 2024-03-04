<?php

namespace AMovil\Reports\ListaExcepciones\Services;

use AMovil\Reports\ListaExcepciones\Domain\ListaExcepcionesRepository;

class ListaExcepcionesFinder
{
    private $repo;

    public function __construct(ListaExcepcionesRepository $repo)
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
