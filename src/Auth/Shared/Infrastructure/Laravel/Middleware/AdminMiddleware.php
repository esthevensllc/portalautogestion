<?php

namespace AMovil\Auth\Shared\Infrastructure\Laravel\Middleware;

use AMovil\Auth\User\Services\GetAuthUser;
use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    private $getAuthUser;

    public function __construct(GetAuthUser $getAuthUser)
    {
        $this->getAuthUser = $getAuthUser;
    }

    public function handle(Request $request, Closure $next, ...$guards)
    {
        try {
            $user = $this->getAuthUser->__invoke();
            if($user->isAdmin === false){
                return abort(403, 'No tiene permisos suficientes para acceder al modulo');
            }
            return $next($request);
        } catch (\Throwable $th) {
            return abort(403, $th->getMessage());
        }
    }
}
