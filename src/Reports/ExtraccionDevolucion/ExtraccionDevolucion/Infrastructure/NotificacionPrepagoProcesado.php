<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Infrastructure;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificacionPrepagoProcesado extends Mailable
{
    use Queueable, SerializesModels;

    protected $ticketOsiptel;
    protected $departamento;
    protected $incidencia;
    protected $reportfile;
    private $subjectToUse;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($ticketOsiptel, $departamento, $incidencia, $reportfile)
    {
        $this->ticketOsiptel = $ticketOsiptel;
        $this->departamento = $departamento;
        $this->incidencia = $incidencia;
        $this->reportfile = $reportfile;
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
        ->view('mails.extraccionProcesadoPrepago')
        ->with([
            'ticket' => $this->ticketOsiptel,
            'departamento' => $this->departamento,
            'incidencia' => $this->incidencia
        ])
        ->attach($this->reportfile["path"], ["as" => $this->reportfile["filename"]]);
    }
}
