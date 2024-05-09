<?php

namespace AMovil\Reports\RepCursado\Services;

use AMovil\Reports\RepCursado\Domain\ReporteCursadoRepository;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportReporteCursado
{
    private $repo;
    private $exportService;
    private $saveReportLog;

    public function __construct(ReporteCursadoRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke(?string $tipo_input, $value, $fecha1, $fecha2): Response
    {
        $dt_start = new DateTime();
        try {
            $dt_fecha1 = DateTime::createFromFormat("Y-m-d", $fecha1);
            $dt_fecha2 = DateTime::createFromFormat("Y-m-d", $fecha2);

            $ciclo = null;

            $dt_fecha_cliclo = clone $dt_fecha2;
            $dt_fecha_cliclo->modify("-1 month");

            $ciclo = $this->getCicloByTipoInput($tipo_input, $value, $dt_fecha1, $dt_fecha_cliclo);
            if($ciclo === null){
                $dt_fecha_cliclo->modify("+1 month");
                $ciclo = $this->getCicloByTipoInput($tipo_input, $value, $dt_fecha1, $dt_fecha_cliclo);
            }

            /*
            switch ($tipo_input) {
                case 'num_cuenta':
                    $ciclo = $this->repo->getCicloByNumCuenta($value, $dt_fecha1, $dt_fecha_cliclo);
                    break;
                case 'cod_cliente':
                    $ciclo = $this->repo->getCicloByCodCliente($value, $dt_fecha1, $dt_fecha_cliclo);
                    break;
                case 'num_documento':
                    $ciclo = $this->repo->getCicloByNumDocumento($value, $dt_fecha1, $dt_fecha_cliclo);
                    break;
                case 'lineas':
                    $lineas = $this->getDataFromExcel($value);
                    if(count($lineas)>0){
                        $ciclo = $lineas[0]['cycle'];
                    }
                    break;
                default:
                    break;
            }*/

            $headers = [
                "str_periodo" => ["label" => "PERIODO"],
                "linea" => ["label" => "LINEA"],
                "servicio" => ["label" => "SERVICIO"],
                "consumo_bytes" => ["label" => "CONSUMO_BYTES"],
            ];

            $options = [
                'title' => 'YYYYMM',
                'sheetIndex' => -1,
                'styles' => [
                    'header' => [
                        'font' => ['bold' => true, 'size' => 9],
                        'borders'=> [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => array('rgb'=>'000000')
                            ]
                        ]
                    ],
                    'body' => [
                        'font' => ['size' => 9]
                    ]
                ]
            ];

            if($ciclo === null){
                throw new Exception("El ciclo null no es valido");
            }
            if((int) $ciclo > 31){
                throw new Exception("El ciclo {$ciclo} no es valido");
            }

            $periodos = $this->getPeriodos($dt_fecha1, $dt_fecha2, $ciclo);
            /*dd([
                "dt_fecha1" => $dt_fecha1,
                "dt_fecha2" => $dt_fecha2,
                "ciclo" => $ciclo,
                "periodos" => $periodos,
            ]);*/
            
            $this->repo->truncateConsolidado();

            foreach($periodos as $row){
                $data = $this->getDateByTipoInput($tipo_input, $value, $row['f1'], $row['f2']);
                $options['sheetIndex'] += 1;
                $options['title'] = $row['periodo'];
                $this->exportService->loadData($headers, $data, $options);
            }

            /*if($dt_fecha1 < $fecha_ini){
                $next_date = (clone $fecha_ini);
                $next_date->modify("-1 day");
                $data = $this->getDateByTipoInput($tipo_input, $value, $dt_fecha1, $next_date);
                $options['sheetIndex'] += 1;
                $options['title'] = $fecha_ini->format("Ym");
                $this->exportService->loadData($headers, $data, $options);
            }

            while ($fecha_ini <= $fecha_fin) {
                $next_date = (clone $fecha_ini);
                $next_date->modify("+1 month");
                $title = $next_date->format("Ym");
                if($next_date >= $fecha_fin){
                    $next_date = (clone $fecha_fin);
                }else{
                    $next_date->modify("-1 day");
                }
                $data = $this->getDateByTipoInput($tipo_input, $value, $fecha_ini, $next_date);
                $options['title'] = $title;
                $options['sheetIndex'] += 1;
                $this->exportService->loadData($headers, $data, $options);

                $fecha_ini->modify("+1 month");
            }*/

            $content = $this->exportService->getWriter(WriterType::XLSX)->getOutput();

            $data = $this->repo->getConsolidado();
            $this->exportService->reset();
            $this->exportService->loadData($headers, $data, []);
            $this->reportLog($this->exportService, $dt_start, new DateTime());

            return new Response([], [
                'filename' => 'REPORTE_TRAFICO_DATOS_'.$dt_fecha1->format("Ymd")."_".$dt_fecha2->format("Ymd").".xlsx",
                "content" => $content
            ]);
        } catch (\Throwable $th) {
            $this->reportLog(null, $dt_start, new DateTime(), ['mensaje' => $th->getMessage()]);
            throw $th;
        }
    }

    private function getCicloByTipoInput($tipo_input, $value, $dt_fecha1, $dt_fecha2){
        $ciclo = null;
        switch ($tipo_input) {
            case 'num_cuenta':
                $ciclo = $this->repo->getCicloByNumCuenta($value, $dt_fecha1, $dt_fecha2);
                break;
            case 'cod_cliente':
                $ciclo = $this->repo->getCicloByCodCliente($value, $dt_fecha1, $dt_fecha2);
                break;
            case 'num_documento':
                $ciclo = $this->repo->getCicloByNumDocumento($value, $dt_fecha1, $dt_fecha2);
                break;
            case 'lineas':
                $lineas = $this->getDataFromExcel($value);
                if(count($lineas)>0){
                    $ciclo = $lineas[0]['cycle'];
                }
                break;
            default:
                break;
        }
        return $ciclo;
    }

    private function getPeriodos(DateTime $fecha1, DateTime $fecha2, $ciclo)
    {
        $fecha_ciclo = DateTime::createFromFormat("Y-m-d", $fecha1->format("Y-m")."-".$ciclo);
        $fecha_inicio = (clone $fecha1);
        $fecha_fin = (clone $fecha2);
        
        $periodos = [];

        if($fecha1->format("Ymd") < $fecha_ciclo->format("Ymd")){
            $periodo = DateTime::createFromFormat("Y-m-d", $fecha1->format("Y-m")."-".$ciclo);

            if($fecha_ciclo->format("Ymd") > $fecha_fin->format("Ymd")){
                $p_fecha_fin = (clone $fecha_fin);
            }else{
                $p_fecha_fin = (clone $periodo);
                $p_fecha_fin->modify("-1 day");
            }

            $periodos[] = ["periodo" => $periodo->format("Ym") , 'f1' => (clone $fecha1), 'f2' => (clone $p_fecha_fin)];
        }
        while ($fecha_ciclo->format("Ymd") <= $fecha_fin->format("Ymd")) {
            $periodo = (clone $fecha_ciclo)->modify("+1 month");
            //$periodo->modify("-1 day");

            $p_fecha_fin = null;
            if($periodo->format("Ymd") > $fecha_fin->format("Ymd")){
                $p_fecha_fin = (clone $fecha_fin);
            }else{
                $p_fecha_fin = (clone $periodo);
                $p_fecha_fin->modify("-1 day");
            }

            if($fecha_inicio->format("Ymd") > $fecha_ciclo->format("Ymd")){
                $p_fecha_inicio = (clone $fecha_inicio);
            }else{
                $p_fecha_inicio = (clone $fecha_ciclo);
            }

            $periodos[] = ["periodo" => $periodo->format("Ym"), 'f1' => (clone $p_fecha_inicio), 'f2' => (clone $p_fecha_fin)];
            $fecha_ciclo->modify("+1 month");
        }
        
        return $periodos;
    }

    private function getDateByTipoInput($tipo_input, $value, $fecha_ini, $next_date){
        $data=[];
        switch ($tipo_input) {
            case 'num_cuenta':
                $data = $this->repo->getReporteByNumCuenta($value, $fecha_ini, $next_date);
                break;
            case 'cod_cliente':
                $data = $this->repo->getReporteByCodCliente($value, $fecha_ini, $next_date);
                break;
            case 'num_documento':
                $data = $this->repo->getReporteByNumCuenta($value, $fecha_ini, $next_date);
                break;
            case 'lineas':
                $lineas = $this->getDataFromExcel($value);
                $data = $this->repo->getReporteByLineas($lineas, $fecha_ini, $next_date);
                break;
            default:
                break;
        }
        return $data;
    }

    private function getDataFromExcel($excel)
    {
        $values = [];
        if ($excel !== null) {
            $reader = IOFactory::createReader('Xlsx');
            $spreedsheet = $reader->load($excel['pathname']);
            $sheet = $spreedsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            for ($i=1; $i <= $highestRow; $i++) {
                $row = [];
                $row['linea'] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
                $row['cycle'] = $sheet->getCellByColumnAndRow(2, $i)->getValue();
                $row['cycle'] = str_pad((string) ((int) $row['cycle']), 2, "0", STR_PAD_LEFT);
                $values[] = $row;
            }
        }
        return $values;
    }

    private function reportLog(?ExportService $exportService, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        $filename = "REPORTE_CURSADO_".$ini->format('YmdHis');
        $data = array_merge([
            'name' => 'DATOS_CURSADOS',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => "reporte-cursados",
        ], $extra_data);
        $this->saveReportLog->fromExport($exportService, $data, $filename, 'DATOS_CURSADOS');
    }
}
