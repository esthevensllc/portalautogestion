<?php

namespace AMovil\Reports\ExtraccionDevFija\InformesFalla\Services;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFallasRepository;
use AMovil\Shared\EmailNotification\Domain\EmailNotification;
use AMovil\Shared\EmailNotification\Domain\EmailNotificationService;
use AMovil\Shared\NotificationUser\Domain\NotificationUserRepository;
use Exception;

class NotifyUsersOnInformeFallasProcessed
{
    private $repo;
    private $extraccionFijaRepo;
    private $notificationUserRepo;
    private $emailNotification;
    private $groupId;

    public function __construct(
        InformeFallasRepository $repo,
        ExtraccionDevFijaRepository $extraccionFijaRepo,
        NotificationUserRepository $notificationUserRepo,
        EmailNotificationService $emailNotification
    ) {
        $this->repo = $repo;
        $this->extraccionFijaRepo = $extraccionFijaRepo;
        $this->notificationUserRepo = $notificationUserRepo;
        $this->emailNotification = $emailNotification;
        $this->groupId = env("APP_ENV")."/extfija_cargado";
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

        $strDepartamentos = $this->getDepartamentos($informe->numero_reporte, $informe->ticket);

        $emails = $this->getUserEmails($users);
        $email = new EmailNotification();
        $email->to($emails)
        ->subject("PROCESADO - TK {$informe->ticket} - {$servicioAfectado->label} - {$informe->name_file}")
        ->view("mails.extraccionFijaProcessed")
        ->with(["informeFallas" => $informe, "departamentos" => $strDepartamentos]);
        $this->emailNotification->send($email);
    }

    private function getUserEmails($users){
        $emails = [];
        foreach($users as $row){
            $emails[] = $row->email;
        }
        return $emails;
    }

    private function getDepartamentos(string $numReporte, string $ticket)
    {
        $input = $this->extraccionFijaRepo->findInputsByNumReporteAndTicket($numReporte, $ticket);
        $departamentosUnicos = [];
        foreach($input->planos as $row){
            $departamentosUnicos[$row->departamento] = null;
        }
        $arrayDepartamentos = [];
        foreach($departamentosUnicos as $dep => $value){
            $arrayDepartamentos[] = $dep;
        }
        return implode(",", $arrayDepartamentos);
    }
}
