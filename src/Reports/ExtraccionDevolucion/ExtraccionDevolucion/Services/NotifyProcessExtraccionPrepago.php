<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Shared\Remedy\Domain\RemedyService;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository as InformeFallaRepository;
use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Infrastructure\NotificacionPrepagoProcesado;
use Illuminate\Support\Facades\Mail;
use Ramsey\Uuid\Uuid;

class NotifyProcessExtraccionPrepago
{
    private $remedy;
    private $informeFallaRepo;
    private $exportPrepago;

    public function __construct(RemedyService $remedy, InformeFallaRepository $informeFallaRepo, ExportRepPrepago $exportPrepago)
    {
        $this->remedy = $remedy;
        $this->informeFallaRepo = $informeFallaRepo;
        $this->exportPrepago = $exportPrepago;
    }

    public function __invoke($ticket, $departamento)
    {
        $informeFalla = $this->informeFallaRepo->findInformeByTicket($ticket);
        $incidencia = $this->remedy->createIncidence(["summary" => $informeFalla->name_file]);
        $incidenciaNumber = $incidencia["incident"];

        $response = $this->exportPrepago->__invoke($ticket, $departamento)->data();
        $tempfile = $this->saveToTempfile($response);

        $mail = new NotificacionPrepagoProcesado($ticket, $departamento, $incidenciaNumber, [
            "path" => $tempfile,
            "filename" => $response["filename"]
        ]);
        $mail->setSubject("PROCESADO - TK {$ticket} - {$informeFalla->name_file} - {$incidenciaNumber}");

        Mail::to([
            'C19884@claro.com.pe',
            'jose.ramosm@claro.com.pe',
            'soporteprepagofactory@claro.com.pe',
            'edward.granados@claro.com.pe',
            'ayskel.guevara@claro.com.pe',
            'elver.ramirez@claro.com.pe',
            'michael.lazaro@claro.com.pe',
            'C25976@claro.com.pe',
            'josias.luna@claro.com.pe',
            'omori@claro.com.pe',
            'cpalacios@claro.com.pe',
            'luyciana.rodriguez@claro.com.pe',
            'C26311@claro.com.pe',
            'marali.huaranca@claro.com.pe',
            'pleon@claro.com.pe',
            'C26559@claro.com.pe',
            'carlos.malpartida@claro.com.pe',
            'lizeth.moya@claro.com.pe',
            'bryan.robles@claro.com.pe',
            'cdiazb@claro.com.pe',
            'C26670@claro.com.pe',
            // 'cclinarez@indracompany.com',
        ])->send($mail);

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
