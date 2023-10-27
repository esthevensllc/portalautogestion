<?php

namespace AMovil\Reports\ExtraccionDevFija\InformesFalla\Services;

use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFallasRepository;
use AMovil\Shared\EmailNotification\Domain\EmailNotification;
use AMovil\Shared\EmailNotification\Domain\EmailNotificationService;
use AMovil\Shared\NotificationUser\Domain\NotificationUserRepository;
use Exception;

class NotifyUsersOnInformeFallasApproved
{
    private $repo;
    private $notificationUserRepo;
    private $emailNotification;
    private $groupId;

    public function __construct(
        InformeFallasRepository $repo,
        NotificationUserRepository $notificationUserRepo,
        EmailNotificationService $emailNotification
    ) {
        $this->repo = $repo;
        $this->notificationUserRepo = $notificationUserRepo;
        $this->emailNotification = $emailNotification;
        $this->groupId = env("APP_ENV")."/extfija_aprobado";
    }

    public function __invoke($numReporte, $servicioAfectadoId)
    {
        $users = $this->notificationUserRepo->getByGroupId($this->groupId);

        $informesFalla = $this->repo->getByCriteria([
            "numero_reporte.eq.{$numReporte}",
            "servicio_afectado_id.eq.{$servicioAfectadoId}"
        ]);
        $servicioAfectado = $this->repo->findServicioAfectado($servicioAfectadoId);
        if(count($informesFalla["data"]) === 0){
            throw new Exception("El informe de fallas no existe");
        }
        $informe = $informesFalla["data"][0];

        $emails = $this->getUserEmails($users);
        $email = new EmailNotification();
        $email->to($emails)
        ->subject("CONTROL REGULATORIO - APROBRADO - TK {$informe->ticket} - {$servicioAfectado->label} - {$informe->name_file}")
        ->view("mails.extraccionFijaApproved")
        ->with(["informeFallas" => $informe, "servicioAfectado" => $servicioAfectado]);
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
