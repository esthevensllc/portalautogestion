<?php

namespace AMovil\Reports\OltCmts\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\OltCmts\Domain\OltCmtsRepository;
use AMovil\Reports\OltCmts\Domain\ReportType;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;
use DateTime;
use Exception;

class ExportOltCmtsReport
{
    private $repo;
    private $exportService;
    private $saveReportLog;
    private $localStorage;
    private $authService;
    private $userIdentifier;

    public function __construct(
        OltCmtsRepository $repo,
        ExportService $exportService,
        SaveReportLog $saveReportLog,
        StorageService $storageService,
        AuthService $authService
    ) {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
        $this->localStorage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
        $this->authService = $authService;
    }

    public function __invoke($typeId, $fecha, $values)
    {
        $dtStart = new DateTime();
        $this->userIdentifier = $this->authService->getUserIdentifier();
        try {
            $dtFecha = DateTime::createFromFormat("Y-m-d", $fecha);
            $data = $this->repo->getOltReport($dtFecha, $values);
            $tempfile = $this->export($data);
            $dtEnd = new DateTime();
            $this->reportLog($tempfile, $dtStart, $dtEnd);
            
            $strDate = $dtFecha->format('Ymd');
            $strNow = $dtEnd->format("YmdHis");
            $filenameToSend = "OLT_CMTS_{$this->userIdentifier}_{$strDate}_{$strNow}.xlsx";
            $content = file_get_contents($tempfile);
            $this->localStorage->put("/space/scripts/joel_mt/reporte_olt_cmts/{$filenameToSend}", $content);

            unlink($tempfile);
            return Response::respData([
                "filename" => "OLT_CMTS_{$strNow}.xlsx",
                "type" => "xlsx",
                "content" => $content
            ]);
        } catch (\Throwable $th) {
            $dtEnd = new DateTime();
            $this->reportLog(null, $dtStart, $dtEnd);
            throw $th;
        }
    }

    private function export($data)
    {
        $headers = [
            "customer_id_codcli" => ['label' => 'CUSTOMER_ID_CODCLI'],
            "fuente" => ['label' => 'FUENTE'],
            "serialnumber" => ['label' => 'SERIALNUMBER'],
            "nombre" => ['label' => 'NOMBRE'],
            "id_card_type_value" => ['label' => 'ID_CARD_TYPE_VALUE'],
            "dni_ruc" => ['label' => 'DNI_RUC'],
            "telefono_claro" => ['label' => 'TELEFONO_CLARO SOCIAL'],
            "numero_adicional" => ['label' => 'NUMERO_ADICIONAL'],
            "servicio_producto" => ['label' => 'SERVICIO_PRODUCTO'],
            "correo" => ['label' => 'CORREO'],
            "distrito" => ['label' => 'DISTRITO'],
            "provincia" => ['label' => 'PROVINCIA'],
            "departamento" => ['label' => 'DEPARTAMENTO'],
            "descripcion_producto" => ['label' => 'DESCRIPCION_PRODUCTO'],
            "agreement_status" => ['label' => 'AGREEMENT_STATUS'],
            "agreement_status_date" => ['label' => 'AGREEMENT_STATUS_DATE'],
            "agreement_start_date" => ['label' => 'AGREEMENT_START_DATE'],
            "agreement_end_date" => ['label' => 'AGREEMENT_END_DATE'],
            "installation_map" => ['label' => 'INSTALLATION_MAP'],
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
            'title' => "OLT_CMTS",
            'styles' => [
                'header' => [
                    'font' => ['bold' => true, 'size' => 9],
                    'borders'=> [
                        'allBorders' => ['borderStyle' => SpreadsheetStyle\Border::BORDER_THIN, 'color' => array('rgb'=>'000000')]
                    ]
                ],
                'body' => [
                    'font' => ['size' => 9],
                ]
            ]
        ]);
        return $this->exportService->getWriter(WriterType::XLSX)->saveToTempfile();
    }

    private function reportLog($tempfile, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        $filename = "OLT_CMTS_".$ini->format('YmdHis').".xlsx";
        $data = array_merge([
            'name' => 'OLT_CMTS',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => 'olt-cmts',
            "filename" => $tempfile !== null ? $filename : null
        ], $extra_data);
        $this->saveReportLog->__invoke($data, $tempfile, 'OLT_CMTS');
    }
}
