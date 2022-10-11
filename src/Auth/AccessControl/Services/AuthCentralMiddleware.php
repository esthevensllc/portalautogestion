<?php

namespace AMovil\Auth\AccessControl\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Auth\User\Services\GetUserModules;
use Illuminate\Http\Request;
use Closure;

class AuthCentralMiddleware
{
    private AuthService $service;
    private GetUserModules $getUserModules;
    
    public function __construct(AuthService $service, GetUserModules $getUserModules)
    {
        $this->service = $service;
        $this->getUserModules = $getUserModules;
    }

    public function handle(Request $request, Closure $next, ...$guards)
    {
        $ticketID = $request->get('ticketID');
        $is_auth = $this->sessionIsValid($ticketID);
        //dd($is_auth);
        if(!$is_auth){
            return redirect($this->service->getConfig('url_login'));
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