<?php

use Illuminate\Support\Facades\Route;

Route::group([
    //'prefix'     => config('backpack.base.route_prefix', ''),
    'middleware' => ['web','amovil.access_log'],
], function () {
    Route::group(['trac_name' => 'auth'], function(){
    Route::get("login", [\AMovil\Auth\AccessControl\Controllers\LoginController::class, "showLoginForm"])->name("login");
    Route::post("login", [\AMovil\Auth\AccessControl\Controllers\LoginController::class, "login"])->name("login_con_usuario");
    Route::get("logout", [\AMovil\Auth\AccessControl\Controllers\LogoutController::class, "__invoke"])->name('logout');
    Route::post("logout", [\AMovil\Auth\AccessControl\Controllers\LogoutController::class, "__invoke"])->name("logout_post");
    });
});

Route::group([
    //'prefix'     => config('backpack.base.route_prefix', ''),
    'middleware' => ['web','amovil.auth', 'amovil.limit_sessions', 'amovil.access_log'],
], function () {
    Route::get("/", function(){
        return redirect("dashboard");
    });
    Route::get("dashboard", [\AMovil\Auth\AccessControl\Controllers\RedirectController::class, "__invoke"]);
});

Route::group([
    'middleware' => ['web','amovil.auth', 'amovil.limit_sessions', 'check.permission', 'amovil.access_log'],
], function () {

    Route::group(['prefix' => 'admin', 'trac_name' => 'admin.usuarios'], function(){
        // Route::get('usuarios', [\AMovil\Auth\User\Controllers\BackpackListUserController::class, 'index']);
        // Route::post('usuarios/search', [\AMovil\Auth\User\Controllers\BackpackListUserController::class, 'search']);
        Route::get('usuarios', [\AMovil\Auth\User\Controllers\ListUsersController::class, 'view'])->name("vista_usuarios");
        Route::post('usuarios/search', [\AMovil\Auth\User\Controllers\ListUsersController::class, 'get'])->name("consultar_usuarios");
        Route::get('usuarios/edit/{id}', [\AMovil\Auth\User\Controllers\EditUserController::class, 'view'])->name("vista_editar_usuario");
        Route::post('usuarios/edit/{id}', [\AMovil\Auth\User\Controllers\EditUserController::class, 'udpate'])->name("editar_usuario");
        Route::get('usuarios/create', [\AMovil\Auth\User\Controllers\CreateUserController::class, 'view'])->name("vista_crear_usuario");
        Route::post('usuarios/create', [\AMovil\Auth\User\Controllers\CreateUserController::class, 'create'])->name("crear_usuario");
        Route::post('usuarios/{id}/status/{status}', [\AMovil\Auth\User\Controllers\ChangeUserStatusController::class, '__invoke'])->name("editar_estado_usuario");
        Route::get('usuarios/{id}', [\AMovil\Auth\User\Controllers\FindUserController::class, 'view'])->name("vista_ver_usuario");
    });

    Route::group(['prefix' => 'admin/roles', 'trac_name' => 'admin.roles'], function(){
        Route::get('/', [\AMovil\Auth\Roles\Controllers\GetRolesController::class, 'view'])->name("vista_roles");
        Route::get('export', [\AMovil\Auth\Roles\Controllers\ExportRolesController::class, 'export'])->name("exportar_roles");
        Route::post('search', [\AMovil\Auth\Roles\Controllers\GetRolesController::class, 'get'])->name("consultar_roles");
        Route::get('edit/{id}', [\AMovil\Auth\Roles\Controllers\UpdateRolController::class, 'view'])->name("vista_editar_rol");
        Route::post('edit/{id}', [\AMovil\Auth\Roles\Controllers\UpdateRolController::class, 'update'])->name("editar_rol");
        Route::post('{id}/status/{status}', [\AMovil\Auth\Roles\Controllers\ChangeRolStatusController::class, '__invoke'])->name("editar_estado_rol");
        Route::get('create', [\AMovil\Auth\Roles\Controllers\CreateRolController::class, 'view'])->name("vista_crear_rol");
        Route::post('/', [\AMovil\Auth\Roles\Controllers\CreateRolController::class, 'create'])->name("crear_rol");
    });
});
