<?php

namespace AMovil\Auth\Roles\Services;

use AMovil\Auth\Roles\Domain\RolRepository;
use AMovil\Auth\Roles\Domain\RolStatus;

class ChangeRolStatus
{
    private $repo;
    public function __construct(RolRepository $repo)
    {
        $this->repo = $repo;
    }
    
    public function __invoke($id, $status)
    {
        RolStatus::guard($status);
        $this->repo->changeStatus($id, $status);
    }
}
