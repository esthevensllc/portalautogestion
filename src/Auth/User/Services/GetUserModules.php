<?php

namespace AMovil\Auth\User\Services;

use AMovil\Auth\Modules\Domain\ModuleRepository;
use AMovil\Auth\Roles\Domain\RolRepository;
use AMovil\Auth\User\Domain\UserRepository;

class GetUserModules
{
    private UserRepository $user_repo;
    private RolRepository $rol_repo;
    private ModuleRepository $module_repo;

    public function __construct(
        UserRepository $user_repo,
        RolRepository $rol_repo,
        ModuleRepository $module_repo
    )
    {
        $this->user_repo = $user_repo;
        $this->rol_repo = $rol_repo;
        $this->module_repo = $module_repo;
    }
    public function __invoke($user_identifier)
    {
        $user = $this->user_repo->findByIdentifier($user_identifier);
        $modules_id = $this->rol_repo->getModulesIdByIds($user->roles_id ?? []);
        $modules_tree = $this->module_repo->getAsTreeByIds($modules_id);
        $modules = $this->module_repo->getByIds($modules_id);
        $modules_by_id = $this->groupBy('id_tracing', $modules);
        return [
            'tree' => $modules_tree,
            'array' => $modules,
            'by_id' => $modules_by_id
        ];
    }

    public function groupBy($key, $modules){
        $by_id = [];
        foreach($modules as $row){
            $by_id[$row->{$key}] = $row;
        }
        return $by_id;
    }
}
