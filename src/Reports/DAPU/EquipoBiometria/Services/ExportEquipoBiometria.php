<?php

namespace AMovil\Reports\DAPU\EquipoBiometria\Services;

use AMovil\Reports\DAPU\EquipoBiometria\Domain\EquipoBiometriaRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportEquipoBiometria
{
    private $repo;
    private $exportService;
    public function __construct(EquipoBiometriaRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($type, $dni, $fono, $periodo): Response
    {
        $data = $this->repo->getByDni_Fono_Periodo($dni, $fono, $periodo);
        $headers = [
            "biometric_date" => ["label" => "BIOMETRIC_DATE"],
            "phone" => ["label" => "PHONE"],
            "iccid" => ["label" => "ICCID"],
            "le" => ["label" => "LE"],
            "agreement_mode_desc" => ["label" => "AGREEMENT_MODE_DESC"],
            "system_code" => ["label" => "SYSTEM_CODE"],
            "operation_type_sc" => ["label" => "OPERATION_TYPE_SC"],
            "operation_type_desc" => ["label" => "OPERATION_TYPE_DESC"],
            "validation_type" => ["label" => "VALIDATION_TYPE"],
            "biometric_pdv_desc" => ["label" => "BIOMETRIC_PDV_DESC"],
            "biometric_pdv_channel_desc" => ["label" => "BIOMETRIC_PDV_CHANNEL_DESC"],
            "is_mobile_desc" => ["label" => "IS_MOBILE_DESC"],
            "customer_document_type_desc" => ["label" => "CUSTOMER_DOCUMENT_TYPE_DESC"],
            "customer_id_card_value" => ["label" => "CUSTOMER_ID_CARD_VALUE"],
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
            'filename' => "EQUIPO_BIOMETRIA_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }
}
