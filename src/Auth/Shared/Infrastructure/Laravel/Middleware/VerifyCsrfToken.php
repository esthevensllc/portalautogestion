<?php

namespace AMovil\Auth\Shared\Infrastructure\Laravel\Middleware;

use App\Http\Middleware\VerifyCsrfToken as MiddlewareVerifyCsrfToken;
use Closure;
use Illuminate\Session\TokenMismatchException;

class VerifyCsrfToken extends MiddlewareVerifyCsrfToken
{
    public function handle($request, Closure $next)
    {
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException $th) {
            $accept = $request->header("accept", "");
            if(str_contains($accept, "application/json")){
                throw $th;
            }
            return redirect()->route("login")->withErrors([
                "username" => "La página a expirado ingresar nuevamente"
            ]);
        }
    }
}
