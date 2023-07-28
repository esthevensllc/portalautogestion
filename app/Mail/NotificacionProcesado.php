<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificacionProcesado extends Mailable
{
    use Queueable, SerializesModels;

    protected $ticketOsiptel;
    protected $depatamento;
    private $subjectToUse;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($ticketOsiptel, $depatamento)
    {
        $this->ticketOsiptel = $ticketOsiptel;
        $this->depatamento = $depatamento;
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
                    ->view('notificacionProcesado')
                    ->with(['ticket' => $this->ticketOsiptel,'departamento' => $this->depatamento]);
    }
}