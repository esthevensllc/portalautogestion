<?php

namespace AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Services;

use AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Domain\InformeCCPPRepository;

class InformeCCPPFinder
{
    private $repo;

    public function __construct(
        InformeCCPPRepository $repo
    ) {
        $this->repo = $repo;
    }

    public function get(){
        return $this->repo->getByCriteria([])["data"];
    }
}
