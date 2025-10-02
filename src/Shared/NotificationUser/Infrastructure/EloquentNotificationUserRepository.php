<?php

namespace AMovil\Shared\NotificationUser\Infrastructure;

use AMovil\Shared\NotificationUser\Domain\NotificationUserRepository;
use Illuminate\Support\Facades\DB;

class EloquentNotificationUserRepository implements NotificationUserRepository
{
    public function getByGroupId(string $groupId)
    {
        return DB::table("usraes.padm_notification_user")
        ->where("group_id", $groupId)
        ->where("status", 1)
        ->get();
    }

    public function getEmailsByGroupId(string $groupId)
    {
        return DB::table("usraes.padm_notification_user")
        ->select("email")
        ->where("group_id", $groupId)
        ->where("status", 1)
        ->get()
        ->map(function($row){
            return $row->email;
        })
        ->toArray();
    }
}
