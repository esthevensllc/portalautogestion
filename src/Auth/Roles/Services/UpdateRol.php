<?php

namespace AMovil\Auth\Roles\Services;

use AMovil\Auth\Roles\Domain\RolRepository;
use AMovil\Auth\Roles\Domain\Validators\UpdateRolValidator;
use AMovil\Shared\Application\Response;

class UpdateRol
{
    private $repo;
    private $validator;

    public function __construct(RolRepository $repo, UpdateRolValidator $validator)
    {
        $this->repo = $repo;
        $this->validator = $validator;
    }

    public function __invoke($data): Response
    {
        $this->validator->validate($data);
        if($this->validator->passes()){
            $this->repo->update($data);
        }
        return new Response($this->validator->errors());
    }
}
