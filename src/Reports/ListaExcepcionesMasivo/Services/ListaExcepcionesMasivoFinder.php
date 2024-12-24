<?php

namespace AMovil\Reports\ListaExcepcionesMasivo\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ListaExcepcionesMasivo\Domain\ListaExcepcionesMasivoRepository;

class ListaExcepcionesMasivoFinder
{
    private $repo;
    private $authService;

    public function __construct(ListaExcepcionesMasivoRepository $repo, AuthService $authService)
    {
        $this->repo = $repo;
        $this->authService = $authService;
    }

    public function getByCriteria($criteria)
    {
        return $this->repo->getByCriteria($criteria);
    }

    public function getUserData()
    {
        $criteria = [
            ["username", $this->authService->getUserIdentifier()]
        ];
        return $this->repo->getByCriteria($criteria);
    }

    public function getTiposOperacion()
    {
        return $this->repo->getTiposOperacion();
    }
}
