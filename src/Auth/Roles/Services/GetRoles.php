<?php

namespace AMovil\Auth\Roles\Services;

use AMovil\Auth\Modules\Domain\ModuleRepository;
use AMovil\Auth\Roles\Domain\RolRepository;

class GetRoles
{
    private RolRepository $repo;
    private ModuleRepository $modulesRepo;

    public function __construct(RolRepository $repo, ModuleRepository $modulesRepo)
    {
        $this->repo = $repo;
        $this->modulesRepo = $modulesRepo;
    }

    public function __invoke()
    {
        $modules = $this->modulesRepo->get()
        ->groupBy('id_tracing')
        ->map(function($row){ return $row[0]; });

        return [
            'relationships' => ['modules_by_id' => $modules],
            'data' => $this->repo->get()
        ];
    }
}
