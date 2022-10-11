<?php

namespace AMovil\Reports\RepDetConsumo\Services;

use AMovil\Reports\RepDetConsumo\Infrastructure\Repository\LaravelDetalleConsumoRepository;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\Style\Border;
use stdClass;
use ZipArchive;

class ExportDetalleConsumoDetallado
{
    private $repo, $exportService, $saveReportLog;
    private $storage_path;
    private $limit_to_paginate = 1000000;

    public function __construct(LaravelDetalleConsumoRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
        $this->storage_path = storage_path('app/public');
    }

    public function __invoke(string $cliente, string $periodo)
    {
        $dt_start = new DateTime();
        try {
            $periodos = explode(",", trim($periodo));
            foreach($periodos as $p){
                $dt_periodo = DateTime::createFromFormat('Ym', $p);
                if(!$dt_periodo){
                    throw new Exception("Periodo invalido");
                }
            }

            $periodos_ok = [];
            foreach($periodos as $p){
                if($this->repo->hasRecords($cliente, $p)){
                    $periodos_ok[] = $p;
                }
            }

            $this->repo->generateReporteDetallado($cliente, $periodos_ok);
            $data = $this->repo->getReporteDetallado($periodos_ok);

            $title = $periodos[0];
            if($title !== $periodos[count($periodos)-1]){
                $title = $periodos[0]." - ".$periodos[count($periodos)-1];
            }
            $headers = [
                "cuenta" => ['label' => "CUENTA"],
                "ciclo" => ['label' => "CICLO"],
                "nro_factura" => ['label' => "NRO_FACTURA"],
                "nro_tel_origen" => ['label' => "NRO_TEL_ORIGEN"],
                "fecha" => ['label' => "FECHA"],
                "hora_inicio" => ['label' => "HORA_INICIO"],
                "hora_fin" => ['label' => "HORA_FIN"],
                "pais" => ['label' => "PAIS"],
                "nro_tel_destino" => ['label' => "NRO_TEL_DESTINO"],
                "consumo" => ['label' => "CONSUMO"],
                "tipo_servicio" => ['label' => "TIPO_SERVICIO"],
                "destino" => ['label' => "DESTINO"],
                "operador" => ['label' => "OPERADOR"],
                "tipo_llamada" => ['label' => "TIPO_LLAMADA"],
                "cargo_final" => ['label' => "CARGO_FINAL"]
            ];
            $options = [
                'title' => $title,
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
            
            // $export = new ExportReporteDetallado($data);
            // $export->setTitle($title);

            $response = ['type' => 'xlsx', 'content' => ''];

            if(count($data) > $this->limit_to_paginate){
                // $this->exportService->reset();
                // $this->exportService->loadData($headers, $data);
                // $this->reportLog($this->exportService, $dt_start, new DateTime());

                $pagination = $this->paginate(count($data), $this->limit_to_paginate);

                $zip = new ZipArchive();
                $str_time = (new DateTime())->format("YmdHis");
                $zipFilename = "{$this->storage_path}/REPORTE_CONSUMO_DETALLADO_{$str_time}.zip";
                $zip->open($zipFilename, ZipArchive::CREATE);
                $files_generated = [];

                for ($i=1; $i <= $pagination['pages']; $i++) { 
                    $range = $pagination['ranges'][(string) $i];
                    // $pag_data = array_slice($data, $range['min'] === 1 ? 0 : $range['min'], $this->limit_to_paginate);
                    $data = $this->repo->getReporteDetallado($periodos_ok, $range['min'] === 1 ? 0 : $range['min'], $this->limit_to_paginate);
                    
                    $exportFilename = "{$this->storage_path}/REPORTE_CONSUMO_DETALLADO_{$str_time}_{$i}.xlsx";
                    $files_generated[] = $exportFilename;
                    $this->exportService->reset();
                    $this->exportService->loadData($headers, $data, $options);
                    $this->exportService->getWriter(WriterType::XLSX)->save($exportFilename);
                    $zip->addFile($exportFilename, "REPORTE_CONSUMO_DETALLADO_{$i}.xlsx");
                }
                $zip->close();
                $files_generated[] = $zipFilename;

                $response = ['type' => 'zip', 'content' => file_get_contents($zipFilename)];

                foreach($files_generated as $file){
                    unlink($file);
                }
            }else{
                $this->exportService->reset();
                $this->exportService->loadData($headers, $data);
                $this->reportLog($this->exportService, $dt_start, new DateTime());

                $this->exportService->reset();
                $this->exportService->loadData($headers, $data, $options);

                $response['type'] = 'xlsx';
                $response['content'] = $this->exportService->getWriter(WriterType::XLSX)->getOutput();
            }

            return $response;
        } catch (\Throwable $th) {
            $this->reportLog(null, $dt_start, new DateTime(), ['mensaje' => $th->getMessage()]);
            throw $th;
        }
    }

    private function reportLog(?ExportService $exportService, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        $filename = "DETALLADO_".$ini->format('YmdHis');
        $data = array_merge([
            'name' => 'DETALLE_CONSUMO',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
        ], $extra_data);
        $this->saveReportLog->fromExport($exportService, $data, $filename, 'DETALLE_CONSUMO');
    }

    public function paginate($count_data, $perPage){
        // $count_data = 2000;
        $pages = ceil($count_data / $perPage);
        $old_max = 1;
        $ranges = [];
        $page = 1;
        $rows = 1;
        while ($page <= $pages) {
            $rows = $page * $perPage;
            $rows = $rows >= $count_data ? $count_data : $rows;
            // $ranges[] = "{$old_max} - {$rows}";
            $ranges[(string) $page] = ['min' => $old_max, 'max' => $rows];
            $old_max = $rows;
            $page++;
        }
        $response = ['pages' => $pages, 'ranges' => $ranges];
        return $response;
    }
}
