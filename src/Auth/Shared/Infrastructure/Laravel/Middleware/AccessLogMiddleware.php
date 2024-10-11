<?php

namespace AMovil\Auth\Shared\Infrastructure\Laravel\Middleware;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Auth\User\Services\GetAuthUserRoles;
use Closure;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccessLogMiddleware
{
    private $authService;
    private $authUserRolesFinder;

    public function __construct(AuthService $authService, GetAuthUserRoles $authUserRolesFinder)
    {
        $this->authService = $authService;
        $this->authUserRolesFinder = $authUserRolesFinder;
    }
    
    public function handle(Request $request, Closure $next, ...$guards)
    {
        if(config("app.env") === "production"){
            $username = $this->authService->getUserIdentifier();
            $response = null;
            $status = "PENDIENTE";
            $statusDetail = null;
            $exception = null;
            $autoLogout = false;
            $rolesName = "";
            if($username !== null){
                $rolesName = $this->getUserRolesName($username);
            }
            try {
                $response = $next($request);
                if(200 <= $response->getStatusCode() && $response->getStatusCode() < 303){
                    $status = "CORRECTO";
                    $statusDetail = "OK";
                    if($request->path() !== "logout" && $username !== null && $this->authService->getUserIdentifier() === null){
                        $status = "BLOQUEADO";
                        $statusDetail = "SESIÓN EXPIRADA";
                    } else if ($response->getStatusCode() === 302){
                        $statusDetail = "REDIRECCIÓN";
                    }
                } else if ($response->getStatusCode() === 401 || $response->getStatusCode() === 403) {
                    $status = "BLOQUEADO";
                } else if (500 <= $response->getStatusCode() && $response->getStatusCode() < 600) {
                    $status = "FALLIDO";
                    $statusDetail = "ERROR INTERNO";
                }
            } catch (\Throwable $th) {
                $status = "FALLIDO";
                $statusDetail = "ERROR INTERNO";
                $exception = $th;
            }
            $autoLogout = $username !== null && $this->authService->getUserIdentifier() === null;

            if($username === null){
                $username = $this->authService->getUserIdentifier();
                if($username !== null){
                    $rolesName = $this->getUserRolesName($username);
                }
            }

            $trac_name = $request->route()->getAction()['trac_name'] ?? null;

            if($username !== null && $request->path() !== "/" && $request->path() !== "dashboard"){
                DB::table("portal_autogestion_access_log")
                ->insert([
                    "session_id" => session()->getId(),
                    "username" => $username,
                    "user_os" => $request->header("User-Agent", "Unknown OS"),
                    "src_ip_address" => $request->ip(),
                    "src_hostname" => gethostbyaddr($request->ip()),
                    "dest_ip_address" => $request->server("SERVER_ADDR"),
                    "dest_hostname" => gethostname(),
                    "module_name" => $trac_name,
                    "module_path" => $request->path(),
                    "module_method" => $request->method(),
                    "module_detalle" => json_encode($this->getRequestData($request)),
                    "access_at" => new DateTime(),
                    "status" => $status,
                    "status_detail" => $statusDetail,
                    "res_status" => $response !== null ? $response->getStatusCode() : null,
                    "user_rol" => $rolesName,
                ]);

                if($request->path() !== "logout" && $autoLogout && $response->getStatusCode() === 302){
                    $this->saveLogoutLog($username, $rolesName, $request);
                }
            }

            if($exception !== null){
                throw $exception;
            }

            return $response;
        }

        return $next($request);
    }

    private function getUserRolesName($username): string {
        $rolesName = "";
        if($username !== null){
            try {
                $rolesName = [];
                $roles = $this->authUserRolesFinder->__invoke();
                foreach ($roles as $row) {
                    $rolesName[] = $row->name;
                }
                $rolesName = implode(",", $rolesName);
            } catch (\AMovil\Auth\User\Domain\Exceptions\UserNotFound $th) {
                $rolesName = "";
            }
        }
        return $rolesName;
    }

    private function getRequestData(Request $request){
        $requestData = $request->route()->parameters();
        foreach ($request->all() as $key => $value) {
            $requestData[$key] = $value;
        }
        if(array_key_exists("password", $requestData)){
            unset($requestData["password"]);
        }
        if(array_key_exists("_token", $requestData)){
            $requestData["_token_csrf"] = $requestData["_token"];
            unset($requestData["_token"]);
        }
        $requestData["action"] = $request->route()->getName();
        return $requestData;
    }

    private function saveLogoutLog($username, $rolesName, Request $request){
        DB::table("portal_autogestion_access_log")
        ->insert([
            "session_id" => session()->getId(),
            "username" => $username,
            "user_os" => $request->header("User-Agent", "Unknown OS"),
            "src_ip_address" => $request->ip(),
            "src_hostname" => gethostbyaddr($request->ip()),
            "dest_ip_address" => $request->server("SERVER_ADDR"),
            "dest_hostname" => gethostname(),
            "module_name" => "auth",
            "module_path" => "logout",
            "module_method" => "POST",
            "module_detalle" => '{"action":"logout_post"}',
            "access_at" => new DateTime(),
            "status" => "CORRECTO",
            "res_status" => 302,
            "status_detail" => "REDIRECCIÓN",
            "user_rol" => $rolesName,
        ]);
    }
}
