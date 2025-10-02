<?php

namespace AMovil\Shared\NotificationUser\Domain;

interface NotificationUserRepository
{
    public function getByGroupId(string $groupId);
    public function getEmailsByGroupId(string $groupId);
}
