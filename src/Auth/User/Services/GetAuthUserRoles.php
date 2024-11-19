<?php

namespace AMovil\Auth\User\Services;

use AMovil\Auth\Roles\Domain\RolRepository;
use AMovil\Shared\Session\Domain\Session;

class GetAuthUserRoles
{
    private $authUserFinder;
    private $rolRepo;
    private $session;

    public function __construct(GetAuthUser $authUserFinder, RolRepository $rolRepo, Session $session)
    {
        $this->authUserFinder = $authUserFinder;
        $this->rolRepo = $rolRepo;
        $this->session = $session;
    }

    public function __invoke()
    {
        $active_roles = $this->session->get("authuser_roles");
        if($active_roles === null){
            $user = $this->authUserFinder->__invoke();
            $active_roles = $this->getActiveRolesId($user);
            $this->session->set("authuser_roles", $active_roles);
            return $active_roles;
        }
        return $active_roles;
    }

    private function getActiveRolesId($user)
    {
        $roles_id = [];
        if($user !== null){
            if ((int) $user->status === 1) {
                $roles_id = $user->roles_id;
            }
        }
        $roles_by_id = $this->rolRepo->get()->groupBy('id');
        $active_roles_id = [];
        foreach($roles_id as $rol_id){
            if((int) $roles_by_id[$rol_id][0]->status === 1){
                $active_roles_id[] = $roles_by_id[$rol_id][0];
            }
        }
        return $active_roles_id;
    }
}
