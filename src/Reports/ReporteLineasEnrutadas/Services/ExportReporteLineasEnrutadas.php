<?php

namespace AMovil\Reports\ReporteLineasEnrutadas\Services;

use AMovil\Reports\ReporteLineasEnrutadas\Domain\ReporteLineasEnrutadasRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style as SpreadsheetStyle;

class ExportReporteLineasEnrutadas
{
    private $repo;
    private $exportService;

    public function __construct(ReporteLineasEnrutadasRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke(?FileInput $file, $operador): Response
    {
        $dataRows = [];

        $dataRows = $this->getMacs($file);
        
        $dtEnd = new DateTime();
        $strNow = $dtEnd->format("Ymd");
        $data = $this->repo->getReporte($dataRows,$operador);
        $tempfile = $this->export($data);
        $content = file_get_contents($tempfile);
        unlink($tempfile);
        return Response::respData([
            "filename" => "REPORTE_LINEAS_ENRUTADAS_{$strNow}.csv",
            "type" => "xlsx",
            "content" => $content
        ]);
    }

    private function getMacs(FileInput $excel)
    {
        $values = [];
        if ($excel !== null) {
            $reader = IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($excel->getFilePath());
            $sheet = $spreadsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();

            // Asume que las fechas están en la primera columna (columna 1) y los teléfonos en la segunda (columna 2)
            for ($i = 1; $i <= $highestRow; $i++) {
                $fechaRaw = $sheet->getCellByColumnAndRow(1, $i)->getValue();
                $telefono = trim($sheet->getCellByColumnAndRow(2, $i)->getValue());

                // Verificar si la fecha es un valor numérico
                if (is_numeric($fechaRaw)) {
                    // Convertir el valor numérico a un objeto DateTime
                    $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fechaRaw)->format('Y-m-d');
                } else {
                    // Si no es numérico, intenta tratarlo como una cadena de fecha
                    $fecha = DateTime::createFromFormat('d/m/Y', $fechaRaw);
                    $fecha = $fecha ? $fecha->format('Y-m-d') : null;
                }

                // Almacenar en un arreglo asociativo
                $values[] = [
                    'fecha' => $fecha,
                    'telefono' => $telefono
                ];
            }
        }
        return $values;
    }

    private function export($data)
    {
        $headers = [
            "day" => ['label' => 'FECHA'],
            "fono1" => ['label' => 'FONO'],
            "flag" => ['label' => 'FLAG']
        ];

        $this->exportService->loadData($headers, $data, [
            'sheetIndex' => 0,
            'rowType' => "array",
            'title' => "REPORTE_LINEAS_ENRUTADAS",
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
