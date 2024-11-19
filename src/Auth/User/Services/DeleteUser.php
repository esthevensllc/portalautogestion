<?php

namespace AMovil\Auth\User\Services;

use AMovil\Auth\User\Domain\UserRepository;
use AMovil\Shared\Application\Response;

class DeleteUser
{
    private $repo;

    public function __construct(UserRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($id): void
    {
        $this->repo->deleteUser($id);
    }
}