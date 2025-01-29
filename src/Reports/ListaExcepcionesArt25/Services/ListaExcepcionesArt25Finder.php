<?php

namespace AMovil\Reports\ListaExcepcionesArt25\Services;

use AMovil\Reports\ListaExcepcionesArt25\Domain\ListaExcepcionesArt25Repository;

class ListaExcepcionesArt25Finder
{
    private $repo;

    public function __construct(ListaExcepcionesArt25Repository $repo)
    {
        $this->repo = $repo;
    }

    public function findLast()
    {
        return $this->repo->findLast();
    }

    public function findByImei($imeis)
    {
        return $this->repo->getByImei($imeis);
    }
}
