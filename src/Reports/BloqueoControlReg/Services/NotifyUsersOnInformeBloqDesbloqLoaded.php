<?php

namespace AMovil\Reports\BloqueoControlReg\Services;

use AMovil\Shared\EmailNotification\Domain\EmailNotification;
use AMovil\Shared\EmailNotification\Domain\EmailNotificationService;
use AMovil\Shared\NotificationUser\Domain\NotificationUserRepository;
use Exception;

class NotifyUsersOnInformeBloqDesbloqLoaded
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
        $this->groupId = env("APP_ENV")."/bloq_desbloq_cargado";
    }

    public function __invoke($report)
    {
        $users = $this->notificationUserRepo->getByGroupId($this->groupId);

        $emails = $this->getUserEmails($users);
        $email = new EmailNotification();
        $email->to($emails)
        ->subject("EJECUCION BLOQ/DESQ - {$report->filename}")
        ->view("mails.bloqueoDesbloqueoLoaded")
        ->with(["reporte" => $report]);
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
