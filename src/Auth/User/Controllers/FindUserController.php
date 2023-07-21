<?php

namespace AMovil\Auth\User\Controllers;

use AMovil\Auth\Roles\Services\GetRoles;
use AMovil\Auth\User\Services\FindUser;
use Illuminate\Http\Request;

class FindUserController
{
    private $service;
    private $getRoles;
    
    public function __construct(FindUser $service, GetRoles $getRoles)
    {
        $this->service = $service;
        $this->getRoles = $getRoles;
    }

    public function view($id)
    {
        $roles = $this->getRoles->__invoke()['data'];
        $user = $this->service->__invoke($id);
        return view('admin.usuarios.find_usuario', compact('roles', 'user'));
    }
}
