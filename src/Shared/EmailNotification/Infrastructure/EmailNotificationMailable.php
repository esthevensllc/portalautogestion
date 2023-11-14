<?php

namespace AMovil\Shared\EmailNotification\Infrastructure;

use AMovil\Shared\EmailNotification\Domain\EmailNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailNotificationMailable extends Mailable
{
    use Queueable, SerializesModels;

    private $emailNotification;

    public function __construct(EmailNotification $emailNotification)
    {
        $this->emailNotification = $emailNotification;
    }

    public function build()
    {
        $this->subject($this->emailNotification->getSubject())
        ->view($this->emailNotification->getView())
        ->with($this->emailNotification->getWith());

        foreach($this->emailNotification->getAttachments() as $file => $options){
            $this->attach($file, $options);
        }

        return $this;
    }
}
