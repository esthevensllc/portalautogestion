<?php

namespace AMovil\Reports\ListaExcepcionesMasivo\Services;

use AMovil\Reports\ListaExcepcionesMasivo\Domain\ListaExcepcionesMasivoRepository;

class ListaExcepcionesMasivoFinder
{
    private $repo;

    public function __construct(ListaExcepcionesMasivoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getByCriteria($criteria)
    {
        return $this->repo->getByCriteria($criteria);
    }

    public function getTiposOperacion()
    {
        return $this->repo->getTiposOperacion();
    }
}
