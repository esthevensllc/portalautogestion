<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\ExtraccionRepository;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository as InformeFallasRepository;
use AMovil\Reports\ExtraccionDevolucion\TicketReports\Domain\TicketReportRepository;
use AMovil\Shared\EmailNotification\Domain\EmailNotification;
use AMovil\Shared\EmailNotification\Domain\EmailNotificationService;
use AMovil\Shared\NotificationUser\Domain\NotificationUserRepository;

class ValidacionRecargaUpdater
{
    private $extraccionRepo;
    private $informeRepo;
    private $ticketRepo;
    private $notificationUserRepo;
    private $emailNotification;
    private $groupId;

    public function __construct(
        ExtraccionRepository $extraccionRepo,
        InformeFallasRepository $informeRepo,
        TicketReportRepository $ticketRepo,
        NotificationUserRepository $notificationUserRepo,
        EmailNotificationService $emailNotification,
    ) {
        $this->extraccionRepo = $extraccionRepo;
        $this->informeRepo = $informeRepo;
        $this->ticketRepo = $ticketRepo;
        $this->notificationUserRepo = $notificationUserRepo;
        $this->emailNotification = $emailNotification;
        $this->groupId = config("app.env")."/ext_acreditado";
    }
    
    public function __invoke($ticket)
    {
        $this->extraccionRepo->saveReporteValidacionRecarga($ticket);
        $this->extraccionRepo->updateNumAcreditadosPrepagoByTicket($ticket);

        $informes = $this->informeRepo->getReportesByCriteria([["ticket", $ticket]]);
        if (count($informes)>0) {
            $informe = $informes[0];
            $registroTicket = $this->ticketRepo->findByTicket($ticket);
            $porcentaje = round($registroTicket->acreditados_pre / $registroTicket->numero_afectados_pre, 2);
            if ($porcentaje > 0.5) {
                $this->informeRepo->acreditadoPre($informe->numero_de_reporte, true);

                $emails = $this->notificationUserRepo->getEmailsByGroupId($this->groupId);
                $email = new EmailNotification();
                $email->to($emails)
                ->subject("Extracción y Devolución / Notificación de ticket acreditado Prepago")
                ->view("mails.extraccionAcreditado")
                ->with(["informeFallas" => $registroTicket, "modalidad" => "Prepago"]);
                $this->emailNotification->send($email);
            } else {
                $this->informeRepo->acreditadoPre($ticket, false);
            }
        } else {
            throw new Exception("No existe un informe de fallas con el ticket ingresado");
        }
    }
}
