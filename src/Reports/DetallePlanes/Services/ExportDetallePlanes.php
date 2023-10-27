<?php

namespace AMovil\Reports\DetallePlanes\Services;

use AMovil\Reports\DetallePlanes\Domain\DetallePlanRepository;
use AMovil\Reports\DetallePlanes\Domain\TipoInputDetallePlan;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportDetallePlanes
{
    private $repo;
    private $exportService;
    
    public function __construct(DetallePlanRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($tipoInputId, $file, $strValue)
    {
        $data = $this->getDataBy($tipoInputId, $file, $strValue);
        $tempfile = $this->export($data);
        $now = new DateTime();
        $content = file_get_contents($tempfile);
        unlink($tempfile);
        return Response::respData([
            "filename" => "DETALLE_DE_PLANES_".$now->format("YmdHis").".xlsx",
            "type" => "xlsx",
            "content" => $content
        ]);
    }

    private function getDataBy(int $tipoInputId, $file, $strValue){
        switch ($tipoInputId) {
            case TipoInputDetallePlan::NUMERO_DOCUMENTO:
                return $this->repo->getByNumDocumento($strValue);
            case TipoInputDetallePlan::NUMERO_CUENTA:
                return $this->repo->getByNumCuenta($strValue);
            case TipoInputDetallePlan::LINEAS:
                $strValue = str_replace(["\r", "\n", "\t"], ["", "", ""], $strValue);
                return $this->repo->getByLineas(explode(",", $strValue));
            case TipoInputDetallePlan::EXCEL:
                $values = $this->getDataFromExcel($file);
                return $this->repo->getByLineas($values);
            default:
                throw new Exception("Tipo de input no valido");
                break;
        }
    }

    private function getDataFromExcel($excel)
    {
        $values = [];
        if ($excel !== null) {
            $reader = IOFactory::createReader('Xlsx');
            $spreedsheet = $reader->load($excel->getPathname());
            $sheet = $spreedsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            for ($i=1; $i <= $highestRow; $i++) {
                $values[] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
            }
        }
        return $values;
    }

    private function export($data)
    {
        $headers = [
            "linea" => ['label' => 'LINEA'],
            "coid" => ['label' => 'COID'],
            "customer_id" => ['label' => 'CUSTOMER_ID'],
            "cuenta" => ['label' => 'CUENTA'],
            "ciclo" => ['label' => 'CICLO'],
            "ruc" => ['label' => 'RUC'],
            "razon_social" => ['label' => 'RAZON SOCIAL'],
            "fec_acti_cta" => ['label' => 'FEC_ACTI_CTA'],
            "modalidad" => ['label' => 'MODALIDAD'],
            "est_linea" => ['label' => 'EST_LINEA'],
            "cod_plan" => ['label' => 'COD_PLAN'],
            "plan" => ['label' => 'PLAN'],
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
            'title' => "DETALLE DE PLANES",
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
}
