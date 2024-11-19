<?php

namespace AMovil\Auth\Shared\Infrastructure\Laravel\Middleware;

use Closure;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LimitSessionsMiddleware
{
    private int $limitSessions = 1;

    public function handle(Request $request, Closure $next, ...$guards)
    {
        if(Auth::check()){
            if($this->isSessionTimeout()){
                Auth::logout();
                session()->flush();
                return redirect()->route("login");
            }

            $userId = Auth::id();
            $sessionId = session()->getId();

            $activeSessions = $this->countActiveSessions($userId);

            if($activeSessions > $this->limitSessions){
                $sessions = $this->getExtraSessions($userId, $sessionId);
                $this->deleteExtraSessions($userId, $sessionId);
                $message = "Se alcanzó el máximo de sesiones permitidas. Se procederá a cerrar las sesiones excedentes";
                return response()->view("auth.limit_sessions", compact("sessions", "message"));
            }
        }
        return $next($request);
    }

    private function isSessionTimeout(): bool {
        $lastActivity = (int) session()->get("lastActivityTime", time());
        $currentTime = time();
        $sessionTimeout = (int) config("session.timeout");

        session()->put("lastActivityTime", $currentTime);
        return $lastActivity > 0 && ($currentTime - $lastActivity) > $sessionTimeout*60;
    }

    public function countActiveSessions($userId): int {
        return DB::table("portal_autogestion_sessions")
        ->where("user_id", $userId)
        ->count();
    }

    public function getExtraSessions($userId, $sessionId){
        $sessions = DB::table("portal_autogestion_sessions")
        ->select("ip_address", "last_activity")
        ->where("user_id", $userId)
        ->where("id", "!=", $sessionId)
        ->get();

        foreach($sessions as $row){
            $date = new DateTime();
            $date->setTimestamp($row->last_activity);
            $row->last_activity = $date->format("Y-m-d H:i:s");
        }
        return $sessions;
    }

    public function deleteExtraSessions($userId, $sessionId){
        DB::table("portal_autogestion_sessions")
        ->where("user_id", $userId)
        ->where("id", "!=", $sessionId)
        ->delete();
    }
}
