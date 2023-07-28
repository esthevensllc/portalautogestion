<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificacionAprobado extends Mailable
{
    use Queueable, SerializesModels;

    protected $reportes;
    private $subjectToUse;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($reportes)
    {
        $this->reportes = $reportes;
    }

    public function setSubject($subject)
    {
        $this->subjectToUse = $subject;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subjectToUse)
                    ->view('notificacionAprobado')
                    ->with(['reportes' => $this->reportes]);
    }
}