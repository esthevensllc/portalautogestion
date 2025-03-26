<?php

namespace AMovil\Reports\RepDetConsumo\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\RepDetConsumo\Infrastructure\Repository\LaravelDetalleConsumoRepository;
use AMovil\Reports\ReportLog\Domain\ReportLogStatus;
use AMovil\Reports\ReportLog\Services\SaveReportDto;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Style\Border;
use stdClass;
use Throwable;
use ZipArchive;

class ExportDetalleConsumoDetallado
{
    private $repo, $exportService, $saveReportLog;
    private $storage_path;
    private $limit_to_paginate = 500000;
    private $userId;
    private $authService;

    public function __construct(LaravelDetalleConsumoRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog, AuthService $authService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
        $this->storage_path = storage_path('app/public');
        $this->authService = $authService;
        $this->userId = $authService->getUserIdentifier();
    }

    public function __invoke(string $cliente, ?string $periodo, $tipo_input, $fecha1, $fecha2, $consumo_sin_cargo)
    {
        $this->userId = $this->authService->getUserIdentifier();
        $dt_start = new DateTime();
        $reportInput = ['tipoInput' => $tipo_input, 'consumoSinCargo' => $consumo_sin_cargo];
        try {
            $periodos = [];
            if($tipo_input === '1'){
                $periodos = explode(",", trim($periodo));
                $reportInput = ["periodos" => $periodos];
            }else{
                $periodos = $this->repo->getPeriodosByFechas(
                    $cliente,
                    DateTime::createFromFormat("Y-m-d", $fecha1),
                    DateTime::createFromFormat("Y-m-d", $fecha2)
                );
                $reportInput["cliente"] = $cliente;
                $reportInput["fechaInicio"] = $fecha1;
                $reportInput["fechaFin"] = $fecha2;
            }
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
            $data = [];
            /*if($tipo_input === '1'){
                $data = $this->repo->getReporteDetallado($periodos_ok, null, null, ["add_sin_cargo" => $consumo_sin_cargo === "1"]);
            }else{
                $data = $this->repo->getReporteDetalladoByFechas(
                    DateTime::createFromFormat("Y-m-d", $fecha1),
                    DateTime::createFromFormat("Y-m-d", $fecha2),
                    null,
                    null,
                    ["add_sin_cargo" => $consumo_sin_cargo === "1"]
                );
            }*/

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

            $request = [
                "tipo_input" => $tipo_input,
                "consumo_sin_cargo" => $consumo_sin_cargo,
            ];
            if($tipo_input === "1"){
                $request["periodos"] = implode(",", $periodos_ok);
            }else{
                $request["fecha1"] = $fecha1;
                $request["fecha2"] = $fecha2;
            }

            
            $http_response = Http::withHeaders([
                "x-user-identifier" => $this->userId,
                // "Content-Type" => "application/json"
            ])
            ->timeout(-1)
            ->post(env("APP_API")."/detalle-consumo/detallado/export", $request);

            /*$client = new \GuzzleHttp\Client();
            $http_response = $client->request('POST', env("APP_API")."/detalle-consumo/detallado/export", [
                "headers" => [
                    "Content-Type" => "application/json",
                    "x-user-identifier" => $this->userId,
                ],
                "body" => json_encode($request)
            ]);*/

            if($http_response->getStatusCode() === 200){
                // $response->toPsrResponse()->getBody();
                // dd($response->body());
                // $stream = $http_response->getBody();
                // $contents = $stream->getContents();
                
                $response["content"] = $http_response->body();
                if(str_contains($http_response->getHeader("Content-Type")[0], "zip")){
                    $response["type"] = "zip";
                }else{
                    $response["type"] = "xlsx";
                }

                $str_time = (new DateTime())->format("YmdHis");
                $filename = "REPORTE_CONSUMO_DETALLADO_{$str_time}.{$response['type']}";
                $temp_file = "{$this->storage_path}/{$filename}";
                $file = fopen($temp_file, "w");
                fwrite($file, $response["content"]);
                fclose($file);

                copy($temp_file, SaveReportLog::LOCAL_PATH."/DETALLE_CONSUMO/{$filename}");


                $this->reportLog($temp_file, $filename, $dt_start, new DateTime(), $reportInput, null);
                unlink($temp_file);

                $response['filename'] = $filename;

                return $response;
            }else{
                $error_message = $http_response->body();
                if(is_array($error_message)){
                    $error_message = json_encode($error_message);
                }

                $exception = new Exception($error_message);
                $this->reportLog(null, null, $dt_start, new DateTime(), $reportInput, $exception);

                throw $exception;
            }

            /*¨
            if(count($data) > $this->limit_to_paginate){

                $pagination = $this->paginate(count($data), $this->limit_to_paginate);

                $zip = new ZipArchive();
                $str_time = (new DateTime())->format("YmdHis");
                $zipFilename = "REPORTE_CONSUMO_DETALLADO_{$str_time}.zip";
                $zipFilePath = "{$this->storage_path}/REPORTE_CONSUMO_DETALLADO_{$str_time}.zip";
                $zip->open($zipFilePath, ZipArchive::CREATE);
                $files_generated = [];

                for ($i=1; $i <= $pagination['pages']; $i++) { 
                    $range = $pagination['ranges'][(string) $i];
                    // $pag_data = array_slice($data, $range['min'] === 1 ? 0 : $range['min'], $this->limit_to_paginate);
                    $data = $this->repo->getReporteDetallado($periodos_ok, $range['min'] === 1 ? 0 : $range['min'], $this->limit_to_paginate, ["add_sin_cargo" => $consumo_sin_cargo === "1"]);
                    
                    $exportFilename = "{$this->storage_path}/REPORTE_CONSUMO_DETALLADO_{$str_time}_{$i}_{$this->userId}.xlsx";
                    $files_generated[] = $exportFilename;
                    $this->saveExportDetallado($title, $data, "REPORTE_CONSUMO_DETALLADO_{$str_time}_{$i}_{$this->userId}.xlsx");
                    $zip->addFile($exportFilename, "REPORTE_CONSUMO_DETALLADO_{$i}.xlsx");
                }
                $zip->close();
                $files_generated[] = $zipFilePath;

                copy($zipFilePath, SaveReportLog::LOCAL_PATH."/REPORTE_CONSUMO_DETALLADO_{$str_time}.zip");

                $this->reportLog($filePath, "REPORTE_CONSUMO_DETALLADO_{$str_time}.zip", $dt_start, new DateTime(), $reportInput, null);

                $response = ['type' => 'zip', 'content' => file_get_contents($zipFilePath), 'filename' => $zipFilename];

                foreach($files_generated as $file){
                    unlink($file);
                }
            }else{
                $this->exportService->reset();
                $this->exportService->loadData($headers, $data, $options);

                $filename = "DETALLADO_".$ini->format('YmdHis').".xlsx";
                $filePath = SaveReportLog::LOCAL_PATH.'/'.$filename;

                $this->exportService->getWriter(WriterType::XLSX)->save($filePath);

                $this->reportLog($filePath, $filename, $dt_start, new DateTime(), $reportInput, null);

                $response['type'] = 'xlsx';
                $response['filename'] = $filename;
                $response['content'] = file_get_contents($filePath);
            }
            */

            return $response;
        } catch (\Throwable $th) {
            $this->reportLog(null, null, $dt_start, new DateTime(), $reportInput, $th);
            throw $th;
        }
    }

    private function saveExportDetallado($title, $data, $exportFilename)
    {
        // $this->exportService->reset();
        // $this->exportService->loadData($headers, $data, $options);
        // $this->exportService->getWriter(WriterType::XLSX)->save($exportFilename);
        $export = new ExportReporteDetallado($data);
        $export->setTitle($title);
        Excel::store($export, $exportFilename, 'public');
    }

    private function reportLog(?string $local_file, ?string $filename, DateTime $ini, DateTime $fin, array $input, ?Throwable $error)
    {
        $logDto = SaveReportDto::create(
            'DETALLE_CONSUMO',
            $filename,
            $ini,
            $fin,
            $error === null ? ReportLogStatus::CORRECTO : ReportLogStatus::ERROR,
            $error !== null ? $error->getMessage() : null,
            'detallle_consumo.detallado',
            json_encode($input)
        );
        $this->saveReportLog->create($logDto);
        if ($local_file !== null) {
            $this->saveReportLog->sendFileToRemoteServer($local_file, "DETALLE_CONSUMO/{$filename}");
        }
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
