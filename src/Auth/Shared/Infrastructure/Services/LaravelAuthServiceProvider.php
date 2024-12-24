<?php

namespace AMovil\Auth\Shared\Infrastructure\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Auth\AccessControl\Infrastructure\Services\AuthCentral;
use Illuminate\Support\ServiceProvider;

class LaravelAuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Access control
        $this->app->bind(AuthService::class, function($app){
            return new AuthCentral(
                env('CAS_URL_AUTH'),
                env('CAS_SESSION_NAME'),
                env('CAS_API_KEY'),
                env('CAS_APP_SECRET'),
                env('CAS_REDIRECT_TO')
            );
        });

        // Session
        $this->app->bind(
            \AMovil\Shared\Session\Domain\Session::class,
            \AMovil\Shared\Session\Infrastructure\LaravelSession::class
        );

        // Modules
        $this->app->bind(
            \AMovil\Auth\Modules\Domain\ModuleRepository::class,
            \AMovil\Auth\Modules\Infrastructure\Repository\EloquentModuleRepository::class
        );

        // Roles
        $this->app->bind(
            \AMovil\Auth\Roles\Domain\RolRepository::class,
            \AMovil\Auth\Roles\Infrastructure\Repository\EloquentRolRepository::class
        );

        // User
        $this->app->bind(
            \AMovil\Auth\User\Domain\UserRepository::class,
            \AMovil\Auth\User\Infrastructure\Repository\EloquentUserRepository::class
        );
    }
}
