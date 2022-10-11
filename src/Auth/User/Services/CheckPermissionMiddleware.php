<?php

namespace AMovil\Auth\User\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use Illuminate\Http\Request;
use Closure;

class CheckPermissionMiddleware
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
        $path_url = $request->path();
        if(!$this->hasAccessTo($path_url)){
            return abort(403, 'No tiene permisos suficientes para acceder al modulo');
        }

        return $next($request);
    }

    private function hasAccessTo($path_url)
    {
        $identifier = $this->service->getUserIdentifier();
        $response = $this->getUserModules->__invoke($identifier);
        $modules = $this->getUserModules->groupBy('url', $response['array']);
        
        foreach($modules as $row){
            // if(preg_match("/^".$row->url."\//i", $path_url."/")){
            if(str_starts_with("$path_url/", "{$row->url}/")){
                return true;
            }
        }
        return false;
    }
}
