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
    
    public function __invoke($tickets)
    {
        $ticketsArray = explode(",", str_replace(" ", "", $tickets));

        if (count($ticketsArray) > 100) {
            return Response::respError(["message" => "No se puede ingresar mas de 100 tickets"]);
        }

        $this->extraccionRepo->saveReporteValidacionRecarga($ticketsArray);
        $this->extraccionRepo->updateNumAcreditadosPrepagoByTicket($ticketsArray);

        $registrosTicket = $this->ticketRepo->getByCriteria([["ticket", $ticketsArray]]);
        if (count($registrosTicket) === 0) {
            throw new Exception("No existe ningun registro de los tickets ingresados");
        }
        foreach ($registrosTicket as $key => $registroTicket) {
            // $registroTicket = $this->ticketRepo->findByTicketAndDepartamento($informe->ticket, $informe->departamento);
            $porcentaje = round($registroTicket->acreditados_pre / $registroTicket->numero_afectados_pre, 2);
            if ($porcentaje > 0.5) {
                $emails = $this->notificationUserRepo->getEmailsByGroupId($this->groupId);
                $email = new EmailNotification();
                $email->to($emails)
                ->subject("Extracción y Devolución / Notificación de ticket acreditado Prepago")
                ->view("mails.extraccionAcreditado")
                ->with(["informeFallas" => $registroTicket, "modalidad" => "Prepago"]);
                $this->emailNotification->send($email);
            }

            $registroTicket = $this->ticketRepo->findSumAcreditadosByTicket($registroTicket->ticket);
            $porcentaje = round($registroTicket->acreditados_pre / $registroTicket->numero_afectados_pre, 2);

            $informe = $this->informeRepo->getReportesByCriteria([["ticket", $registroTicket->ticket]]);
            $informe = count($informe) > 0 ? $informe[0] : null;

            if ($informe !== null) {
                if ($porcentaje > 0.5) {
                    $this->informeRepo->acreditadoPre($informe->numero_de_reporte, true);
                } else {
                    $this->informeRepo->acreditadoPre($informe->numero_de_reporte, false);
                }
            }

        }
    }
}
