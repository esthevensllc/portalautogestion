<?php

namespace AMovil\Reports\DetallePlanes\Services;

use AMovil\Reports\DetallePlanes\Domain\DetallePlanRepository;

class DetallePlanesFinder
{
    private $repo;
    
    public function __construct(DetallePlanRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getTiposInput()
    {
        return $this->repo->getTiposInput();
    }
}
