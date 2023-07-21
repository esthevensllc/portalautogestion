<?php

namespace AMovil\Auth\Roles\Services;

use AMovil\Auth\Roles\Domain\RolRepository;
use AMovil\Shared\Application\Response;

class FindRol
{
    private $repo;

    public function __construct(RolRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($id): Response
    {
        $data = $this->repo->findById($id);
        return new Response([], $data);
    }
}
