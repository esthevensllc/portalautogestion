<?php

namespace AMovil\Auth\Roles\Controllers;

use AMovil\Auth\Roles\Services\GetRoles;

class GetRolesController
{
    private $service;

    public function __construct(GetRoles $service)
    {
        $this->service = $service;
    }

    public function view(){
        return view('admin.roles.listar_roles');
    }

    public function get(){
        $response = $this->service->__invoke();
        return response()->json($response);
    }
}
