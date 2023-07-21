<?php

namespace AMovil\Auth\Roles\Controllers;

use AMovil\Auth\Modules\Services\GetModules;
use AMovil\Auth\Roles\Services\FindRol;
use AMovil\Auth\Roles\Services\UpdateRol;
use Illuminate\Http\Request;

class UpdateRolController
{
    private $service;
    private $findRol;
    private $getModules;

    public function __construct(UpdateRol $service, FindRol $findRol, GetModules $getModules)
    {
        $this->service = $service;
        $this->findRol = $findRol;
        $this->getModules = $getModules;
    }

    public function view($id)
    {
        $rol = $this->findRol->__invoke($id)->data();
        $modules = $this->getModules->__invoke()->data();
        $config = [
            'title' => 'Editar rol',
            'url' => asset('admin/roles/edit')."/{id}",
            'success_message' => 'Se actualizó correctamente',
            'error_message' => 'Ocurrió un error al actualizar',
        ];
        return view('admin.roles.update_rol', compact('rol', 'modules','config'));
    }

    public function update(Request $request)
    {
        $response = $this->service->__invoke($request->all());
        return response()->json($response->toArray());
    }
}
