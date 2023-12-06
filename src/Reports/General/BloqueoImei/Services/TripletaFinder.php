<?php

namespace AMovil\Reports\General\BloqueoImei\Services;

use AMovil\Reports\General\BloqueoImei\Domain\BloqueoImeiRepository;

class TripletaFinder
{
    private $repo;

    public function __construct(BloqueoImeiRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getBy($field, $value)
    {
        return $this->repo->getTripletaByCriteria([[$field, $value]]);
    }
}
