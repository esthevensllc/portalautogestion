<?php

namespace AMovil\Auth\Shared\Infrastructure\Laravel\Middleware;

use AMovil\Auth\AccessControl\Domain\AuthService;
use Closure;
use Illuminate\Http\Request;

class AuthenticationMiddleware
{
    private AuthService $service;
    private $redirecTo = "login";
    
    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    public function handle(Request $request, Closure $next, ...$guards)
    {
        $ticketID = $request->get('ticketID');
        $is_auth = $this->sessionIsValid($ticketID);
        if(!$is_auth){
            $this->service->logout();
            return redirect($this->redirecTo);
        }

        return $next($request);
    }
    
    private function sessionIsValid($ticketID): bool
    {
        $params = ['ticketID' => $ticketID];
        $is_auth = $this->service->validateSession($params);
        return $is_auth;
    }
}
