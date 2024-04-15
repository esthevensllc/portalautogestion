<?php

namespace AMovil\Reports\ClientesMacSn\Services;

use AMovil\Reports\ClientesMacSn\Domain\ClientesMacSnRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportClientesMacSn
{
    private $repo;
    private $exportService;

    public function __construct(ClientesMacSnRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke(string $inputType, ?FileInput $file, $mac, $fecha): Response
    {
        $dtFecha = DateTime::createFromFormat("Y-m-d", $fecha);
        $macs = [];
        if($inputType === "tab_macs"){
            $macs = explode(",", str_replace(" ", "", $mac));
        }else if($inputType === "tab_excel"){
            $macs = $this->getMacs($file);
        }
        $dtEnd = new DateTime();
        $strNow = $dtEnd->format("Ymd");
        $data = $this->repo->getReporte($macs, $dtFecha);
        $tempfile = $this->export($data);
        $content = file_get_contents($tempfile);
        unlink($tempfile);
        return Response::respData([
            "filename" => "CLIENTES_MAC_SN_{$strNow}.csv",
            "type" => "xlsx",
            "content" => $content
        ]);
    }

    private function getMacs(FileInput $excel)
    {
        $values = [];
        if ($excel !== null) {
            $reader = IOFactory::createReader('Xlsx');
            $spreedsheet = $reader->load($excel->getFilePath());
            $sheet = $spreedsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            for ($i=1; $i <= $highestRow; $i++) {
                $values[] = trim($sheet->getCellByColumnAndRow(1, $i)->getValue());
            }
        }
        return $values;
    }

    private function export($data)
    {
        $headers = [
            "mac_sn" => ['label' => 'MAC_SN'],
            "customer_id_codcli" => ['label' => 'CUSTOMER_ID_CODCLI'],
            "modalidad" => ['label' => 'MODALIDAD'],
            "customer_account_name" => ['label' => 'CUSTOMER_ACCOUNT_NAME'],
            "tip_documento" => ['label' => 'TIPO DOCUMENTO'],
            "nro_documento" => ['label' => 'NUMERO DOCUMENTO'],
            "direccion" => ['label' => 'DIRECCIÓN'],
            "fecha_activacion" => ['label' => 'FECHA DE ACTIVACIÓN'],
            "estado" => ['label' => 'ESTADO'],
            "fecha_status" => ['label' => 'FECHA DE ESTADO'],
            "motivo_de_estado" => ['label' => 'MOTIVO DE ESTADO']
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
            'rowType' => "array",
            'title' => "CLIENTES_MAC",
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
}
