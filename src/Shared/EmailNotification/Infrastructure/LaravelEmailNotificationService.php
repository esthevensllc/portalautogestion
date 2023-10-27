<?php

namespace AMovil\Shared\EmailNotification\Infrastructure;

use AMovil\Shared\EmailNotification\Domain\EmailNotification;
use AMovil\Shared\EmailNotification\Domain\EmailNotificationService;
use Illuminate\Support\Facades\Mail;

class LaravelEmailNotificationService implements EmailNotificationService
{
    public function send(EmailNotification $emailNotification)
    {
        $mailable = new EmailNotificationMailable($emailNotification);

        Mail::to($emailNotification->getTo())->send($mailable);
    }
}
