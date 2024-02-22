<?php

namespace AMovil\Reports\ClientesPlanos\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ClientesPlanos\Domain\ClientesPlanosRepository;
use AMovil\Reports\ClientesPlanos\Domain\ReportType;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;
use DateTime;
use Exception;

class ExportClientesPlanosReport
{
    private $repo;
    private $exportService;
    private $saveReportLog;
    private $localStorage;
    private $authService;
    private $userIdentifier;
    private $inputFinder;

    public function __construct(
        ClientesPlanosRepository $repo,
        ExportService $exportService,
        SaveReportLog $saveReportLog,
        StorageService $storageService,
        AuthService $authService,
        ClientesPlanosFinder $inputFinder
    ) {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
        $this->localStorage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
        $this->authService = $authService;
        $this->inputFinder = $inputFinder;
    }

    public function __invoke(int $typeId, $fecha, $values)
    {
        $dtStart = new DateTime();
        $this->userIdentifier = $this->authService->getUserIdentifier();
        try {
            $dtFecha = DateTime::createFromFormat("Y-m-d", $fecha);
            $data = [];
            if($typeId === 1){
                if(in_array("all", $values)){
                    $values = $this->getAllInputs($typeId, $dtFecha);
                }
                $data = $this->repo->getOltReport($dtFecha, $values);
                $tipo_reporte = 'OLT';
            } elseif ($typeId === 2){
                if(in_array("all", $values)){
                    $values = $this->getAllInputs($typeId, $dtFecha);
                }
                $data = $this->repo->getCmtsReport($dtFecha, $values);
                $tipo_reporte = 'CMTS';
            } else {
                throw new Exception("El tipo de reporte no es valido");
            }
            $tempfile = $this->export($data);
            $dtEnd = new DateTime();
            $this->reportLog($tempfile, $dtStart, $dtEnd);
            
            $strDate = $dtFecha->format('Ymd');
            $strNow = $dtEnd->format("YmdHis");
            $filenameToSend = "PLANOS_{$tipo_reporte}_{$this->userIdentifier}_{$strNow}.csv";
            $content = file_get_contents($tempfile);
            $this->localStorage->put("/space/scripts/joel_mt/portal_autogestion/clientes_planos/reportes/{$filenameToSend}", $content);

            unlink($tempfile);
            return Response::respData([
                "filename" => $filenameToSend,
                "type" => "csv",
                "content" => $content
            ]);
        } catch (\Throwable $th) {
            $dtEnd = new DateTime();
            $this->reportLog(null, $dtStart, $dtEnd);
            throw $th;
        }
    }

    private function getAllInputs(int $typeId, Datetime $fecha)
    {
        if($typeId === 1){
            $inputs = $this->inputFinder->getOltsValues($fecha);
            $values = [];
            foreach($inputs as $row){
                $values[] = $row->id;
            }
            return $values;
        } elseif ($typeId === 2){
            $inputs = $this->inputFinder->getCmtsValues($fecha);
            $values = [];
            foreach($inputs as $row){
                $values[] = $row->id;
            }
            return $values;
        } else {
            throw new Exception("El tipo de reporte no es valido");
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
            "direccion" => ['label' => 'DIRECCION'],
            "distrito" => ['label' => 'DISTRITO'],
            "provincia" => ['label' => 'PROVINCIA'],
            "departamento" => ['label' => 'DEPARTAMENTO'],
            "descripcion_producto" => ['label' => 'DESCRIPCION_PRODUCTO'],
            "agreement_status" => ['label' => 'AGREEMENT_STATUS'],
            "agreement_status_date" => ['label' => 'AGREEMENT_STATUS_DATE'],
            "agreement_start_date" => ['label' => 'AGREEMENT_START_DATE'],
            "agreement_end_date" => ['label' => 'AGREEMENT_END_DATE'],
            "installation_map" => ['label' => 'INSTALLATION_MAP'],
            "numero" => ['label' => 'NUMERO'],
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
            'title' => "CLIENTES_PLANOS",
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
        return $this->exportService->getWriter(WriterType::CSV)->saveToTempfile();
    }

    private function reportLog($tempfile, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        $filename = "CLIENTES_PLANOS_".$ini->format('YmdHis').".csv";
        $data = array_merge([
            'name' => 'CLIENTES_PLANOS',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => 'clientes-planos',
            "filename" => $tempfile !== null ? $filename : null
        ], $extra_data);
        $this->saveReportLog->__invoke($data, $tempfile, 'CLIENTES_PLANOS');
    }
}
