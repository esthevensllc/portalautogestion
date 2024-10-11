<?php

namespace AMovil\Auth\User\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Auth\User\Domain\UserRepository;
use Exception;

class GetAuthUser
{
    private $userRepo;
    private $authService;

    public function __construct(UserRepository $userRepo, AuthService $authService)
    {
        $this->userRepo = $userRepo;
        $this->authService = $authService;
    }

    public function __invoke(): object
    {
        $authUser = $this->userRepo->findByIdentifier($this->authService->getUserIdentifier());
        if($authUser === null){
            throw new \AMovil\Auth\User\Domain\Exceptions\UserNotFound("EL usuario no existe");
        }
        $authUser->isAdmin = in_array(1, $authUser->roles_id);
        return $authUser;
    }
}
