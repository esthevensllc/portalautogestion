<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificacionProcesado extends Mailable
{
    use Queueable, SerializesModels;

    protected $reportes;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($reportes)
    {
        $this->reportes = $reportes;
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
                    ->with(['reportes' => $this->reportes]);
    }
}