<?php

namespace AMovil\Shared\EmailNotification\Domain;

interface EmailNotificationService
{
    public function send(EmailNotification $emailNotification);
}
