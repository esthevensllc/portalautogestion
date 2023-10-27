<?php

namespace AMovil\Reports\DetallePlanes\Planes\Services;

use AMovil\Reports\DetallePlanes\Planes\Domain\DetallePlanRepository;

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
