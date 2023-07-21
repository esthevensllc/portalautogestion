<?php

namespace AMovil\Auth\Roles\Controllers;

use AMovil\Auth\Modules\Services\GetModules;
use AMovil\Auth\Roles\Services\CreateRol;
use AMovil\Auth\Roles\Services\FindRol;
use Illuminate\Http\Request;

class CreateRolController
{
    private $service;
    private $getModules;

    public function __construct(CreateRol $service, GetModules $getModules)
    {
        $this->service = $service;
        $this->getModules = $getModules;
    }

    public function view()
    {
        $rol = null;
        $modules = $this->getModules->__invoke()->data();
        $config = [
            'title' => 'Crear rol',
            'url' => asset('admin/roles'),
            'success_message' => 'Se registro correctamente',
            'error_message' => 'Ocurrió un error al registrar',
        ];
        return view('admin.roles.update_rol', compact('rol', 'modules','config'));
    }

    public function create(Request $request)
    {
        $response = $this->service->__invoke($request->all());
        return response()->json($response->toArray());
    }
}
