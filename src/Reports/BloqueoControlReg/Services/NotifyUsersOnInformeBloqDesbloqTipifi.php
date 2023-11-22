<?php

namespace AMovil\Reports\BloqueoControlReg\Services;

use AMovil\Shared\EmailNotification\Domain\EmailNotification;
use AMovil\Shared\EmailNotification\Domain\EmailNotificationService;
use AMovil\Shared\NotificationUser\Domain\NotificationUserRepository;
use Exception;

class NotifyUsersOnInformeBloqDesbloqTipifi
{
    private $notificationUserRepo;
    private $emailNotification;
    private $groupId;

    public function __construct(
        NotificationUserRepository $notificationUserRepo,
        EmailNotificationService $emailNotification
    ) {
        $this->notificationUserRepo = $notificationUserRepo;
        $this->emailNotification = $emailNotification;
        $this->groupId = env("APP_ENV")."/bloq_desbloq_tipifi";
    }

    public function __invoke($tipoOperacionId, $tipoDocumentoId, $documento, $imei, $tipificacionId, $ticklerId, $instantaneo, $notas)
    {
        $users = $this->notificationUserRepo->getByGroupId($this->groupId);

        $emails = $this->getUserEmails($users);
        $email = new EmailNotification();
        $email->to($emails)
        ->subject("ENVIO DE TIPIFICACION - {$tipificacionId}")
        ->view("mails.bloqueoDesbloqueoTipifi")
        ->with(["tipoOperacionId" => $tipoOperacionId,"tipoDocumentoId" => $tipoDocumentoId,"documento" => $documento,"imei" => $imei,"tipificacionId" => $tipificacionId,"ticklerId" => $ticklerId,"instantaneo" => $instantaneo,"notas" => $notas]);
        $this->emailNotification->send($email);
    }

    private function getUserEmails($users){
        $emails = [];
        foreach($users as $row){
            $emails[] = $row->email;
        }
        return $emails;
    }
}
