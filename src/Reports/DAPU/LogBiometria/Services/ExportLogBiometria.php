<?php

namespace AMovil\Reports\DAPU\LogBiometria\Services;

use AMovil\Reports\DAPU\LogBiometria\Domain\LogBiometriaRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportLogBiometria
{
    private $repo;
    private $exportService;
    public function __construct(LogBiometriaRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($type, $dni, $msisdn): Response
    {
        $data = $this->repo->getByDniAndMsisdn($dni, $msisdn);
        $headers = [
            "fecha_registro_transaccion" => ["label" => "FECHA_REGISTRO_TRANSACCION"],
            "num_doc" => ["label" => "NUM_DOC"],
            "numero_transaccion" => ["label" => "NUMERO_TRANSACCION"],
            "codigo_respuesta" => ["label" => "CODIGO_RESPUESTA"],
            "mensaje_respuesta" => ["label" => "MENSAJE_RESPUESTA"],
            "marca_dispositivo" => ["label" => "MARCA_DISPOSITIVO"],
            "modelo_dispositivo" => ["label" => "MODELO_DISPOSITIVO"],
            "version_aplicativo" => ["label" => "VERSION_APLICATIVO"],
            "codigo_aplicativo" => ["label" => "CODIGO_APLICATIVO"],
            "modelo_estacion" => ["label" => "MODELO_ESTACION"],
            "codigo_identi_estacion" => ["label" => "CODIGO_IDENTI_ESTACION"],
            "subscription_access_number" => ["label" => "SUBSCRIPTION_ACCESS_NUMBER"],
            "agreement_status_date" => ["label" => "AGREEMENT_STATUS_DATE"],
        ];

        $options = [
            'styles' => [
                'header' => ['font' => ['bold' => true]]
            ]
        ];

        $this->exportService->loadData($headers, $data, $options);
        $content = $this->exportService->getWriter($type)->getOutput();
        $dt = new DateTime();
        
        return new Response([], [
            'filename' => "LOG_BIOMETRIA_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }
}
