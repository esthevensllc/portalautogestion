<?php

namespace AMovil\Auth\User\Controllers;

use AMovil\Auth\User\Services\GetUsers;

class ListUsersController
{
    private $service;

    public function __construct(GetUsers $service)
    {
        $this->service = $service;
    }

    public function view(){
        return view('admin.usuarios.listar_usuarios');
    }

    public function get(){
        $response = $this->service->__invoke();
        return response()->json($response);
    }
}
