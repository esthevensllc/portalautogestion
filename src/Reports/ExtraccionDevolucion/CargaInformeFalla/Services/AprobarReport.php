<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\InformeStatus;
use AMovil\Shared\EmailNotification\Domain\EmailNotification;
use AMovil\Shared\EmailNotification\Domain\EmailNotificationService;
use AMovil\Shared\NotificationUser\Domain\NotificationUserRepository;
use App\Mail\NotificacionAprobado;
use DateTime;
use Illuminate\Support\Facades\Mail;

class AprobarReport
{
    private $repo;
    private $authService;
    private $notificationUserRepo;
    private $emailNotification;
    private $groupId;
    
    public function __construct(ExtraccionRepository $repo, AuthService $authService, NotificationUserRepository $notificationUserRepo, EmailNotificationService $emailNotification)
    {
        $this->repo = $repo;
        $this->authService = $authService;
        $this->notificationUserRepo = $notificationUserRepo;
        $this->emailNotification = $emailNotification;
        $this->groupId = config("app.env")."/ext_aprobado";
    }

    public function __invoke($id,$ticket,$userIpAddress)
    {
        $reportes = $this->repo->findInputFor($id);
        if(count($reportes) === 0){
            return [
                "result" => false,
                "message" => "El informe de fallas no existe"
            ];
        }

        $data = $this->repo->aprobar($id,$ticket);
        if($data){
            $this->repo->registerStatusChanges($id, null, $this->authService->getUserIdentifier(), $userIpAddress, InformeStatus::APROBADO, new DateTime());
            $reportes = $this->repo->findInputFor($id);
            $reporteFinded = $reportes[0];
            
            $emails = $this->notificationUserRepo->getEmailsByGroupId($this->groupId);
            $email = new EmailNotification();
            $email->to($emails)
            ->subject("CONTROL REGULATORIO - APROBRADO - TK {$ticket} - {$reporteFinded->name_file}")
            ->view("notificacionAprobado")
            ->with(["reportes" => $reportes]);
            $this->emailNotification->send($email);

            return [
                "result" => true,
                "reportes" => $reportes
            ];
        }

        return [
            "result" => false,
            "message" => "El n\u00famero de Ticket ya existe, no se aprob\u00f3"
        ];
    }
}