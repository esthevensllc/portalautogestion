<?php

namespace AMovil\Auth\User\Services;

use AMovil\Auth\User\Domain\UserRepository;
use AMovil\Auth\User\Domain\UserStatus;
use AMovil\Shared\Application\Response;

class ChangeUserStatus
{
    private $repo;
    private $validator;

    public function __construct(UserRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($id, $status): void
    {
        UserStatus::guard($status);
        $this->repo->changeStatus($id, $status);
    }
}
