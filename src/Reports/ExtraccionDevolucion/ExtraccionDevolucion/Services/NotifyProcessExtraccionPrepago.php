<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Shared\Remedy\Domain\RemedyService;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository as InformeFallaRepository;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Infrastructure\NotificacionPrepagoProcesado;
use AMovil\Shared\EmailNotification\Domain\EmailNotification;
use AMovil\Shared\EmailNotification\Domain\EmailNotificationService;
use AMovil\Shared\NotificationUser\Domain\NotificationUserRepository;
use Illuminate\Support\Facades\Mail;
use Ramsey\Uuid\Uuid;

class NotifyProcessExtraccionPrepago
{
    private $remedy;
    private $informeFallaRepo;
    private $exportPrepago;
    private $notificationUserRepo;
    private $emailNotification;
    private $groupId;

    public function __construct(RemedyService $remedy, InformeFallaRepository $informeFallaRepo, ExportRepPrepago $exportPrepago, NotificationUserRepository $notificationUserRepo, EmailNotificationService $emailNotification)
    {
        $this->remedy = $remedy;
        $this->informeFallaRepo = $informeFallaRepo;
        $this->exportPrepago = $exportPrepago;
        $this->notificationUserRepo = $notificationUserRepo;
        $this->emailNotification = $emailNotification;
        $this->groupId = config("app.env")."/ext_procesado_prepago";
    }

    public function __invoke($ticket, $departamento)
    {
        $informeFalla = $this->informeFallaRepo->findInformeByTicket($ticket);
        $incidencia = $this->remedy->createIncidence(["summary" => $informeFalla->name_file]);
        $incidenciaNumber = $incidencia["incident"];

        $response = $this->exportPrepago->__invoke($ticket, $departamento)->data();
        $tempfile = $this->saveToTempfile($response);

        $emails = $this->notificationUserRepo->getEmailsByGroupId($this->groupId);
        $email = new EmailNotification();
        $email->to($emails)
        ->subject("PROCESADO - TK {$ticket} - {$informeFalla->name_file} - {$incidenciaNumber}")
        ->view("mails.extraccionProcesadoPrepago")
        ->with([
            'ticket' => $ticket,
            'departamento' => $departamento,
            'incidencia' => $incidenciaNumber
        ])
        ->attach($tempfile, ["as" => $response["filename"]]);
        $this->emailNotification->send($email);

        unlink($tempfile);
    }

    public function saveToTempfile($response)
    {
        $tempFilename = sys_get_temp_dir() ."/phpexport-". Uuid::uuid4()->toString().".tsv";
        $fp = fopen($tempFilename, "w");
        fwrite($fp, $response["content"]);
        fclose($fp);
        return $tempFilename;
    }
}
