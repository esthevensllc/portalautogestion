<?php

namespace AMovil\Auth\Roles\Services;

use AMovil\Auth\Roles\Domain\RolRepository;
use AMovil\Auth\Roles\Domain\Validators\CreateRolValidator;
use AMovil\Shared\Application\Response;

class CreateRol
{
    private $repo;
    private $validator;

    public function __construct(RolRepository $repo)
    {
        $this->repo = $repo;
        $this->validator = new CreateRolValidator();
    }

    public function __invoke($data): Response
    {
        $this->validator->validate($data);
        if($this->validator->passes()){
            $this->repo->create($data);
        }
        return new Response($this->validator->errors());
    }
}
