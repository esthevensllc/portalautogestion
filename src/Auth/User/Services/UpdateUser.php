<?php

namespace AMovil\Auth\User\Services;

use AMovil\Auth\User\Domain\UserRepository;
use AMovil\Auth\User\Domain\Validators\UpdateUserValidator;
use AMovil\Shared\Application\Response;

class UpdateUser
{
    private $repo;
    private $validator;

    public function __construct(UserRepository $repo, UpdateUserValidator $validator)
    {
        $this->repo = $repo;
        $this->validator = $validator;
    }

    public function __invoke($data): Response
    {
        $this->validator->validate($data);
        if ($this->validator->passes()) {
            $this->repo->update($data);
        }
        return new Response($this->validator->errors());
    }
}
