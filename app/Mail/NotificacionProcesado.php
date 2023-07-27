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

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("CARGA DE INFORMES DE FALLAS")
                    ->view('notificacionProcesado')
                    ->with(['ticket' => $this->ticketOsiptel,'depatamento' => $this->depatamento]);
    }
}