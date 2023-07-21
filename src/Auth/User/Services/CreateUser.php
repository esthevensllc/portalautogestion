<?php

namespace AMovil\Auth\User\Services;

use AMovil\Auth\User\Domain\UserRepository;
use AMovil\Auth\User\Domain\Validators\CreateUserValidator;
use AMovil\Shared\Application\Response;

class CreateUser
{
    private $repo;
    private $validator;

    public function __construct(UserRepository $repo, CreateUserValidator $validator)
    {
        $this->repo = $repo;
        $this->validator = $validator;
    }

    public function __invoke($data): Response
    {
        $this->validator->validate($data);
        if ($this->validator->passes()) {
            $this->repo->create($data);
        }
        return new Response($this->validator->errors());
    }
}
