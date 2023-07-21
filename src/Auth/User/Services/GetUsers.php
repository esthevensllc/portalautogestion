<?php

namespace AMovil\Auth\User\Services;

use AMovil\Auth\Roles\Domain\RolRepository;
use AMovil\Auth\User\Domain\UserRepository;

class GetUsers
{
    private UserRepository $repo;
    private RolRepository $rolRepository;

    public function __construct(UserRepository $repo, RolRepository $rolRepository)
    {
        $this->repo = $repo;
        $this->rolRepository = $rolRepository;
    }

    public function __invoke()
    {
        $data = $this->repo->get();
        $rols = $this->rolRepository->get()
        ->groupBy('id')
        ->map(function($items){ return $items[0]; });

        return [
            'relationships' => [
                'rols' => $rols
            ],
            'data' => $data
        ];
    }
}
