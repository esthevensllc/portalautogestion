<?php

namespace AMovil\Auth\User\Services;

use AMovil\Auth\User\Domain\UserRepository;

class FindUser
{
    private UserRepository $repo;

    public function __construct(UserRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($id)
    {
        return $this->repo->findById($id);
    }
}
