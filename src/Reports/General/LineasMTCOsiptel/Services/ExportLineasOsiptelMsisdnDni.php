<?php

namespace AMovil\Reports\General\LineasMTCOsiptel\Services;

use AMovil\Reports\General\LineasMTCOsiptel\Domain\LineasMTCOsiptelRepository;
//use AMovil\Shared\Application\Response;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use DateTime;
use Exception;
use Ramsey\Uuid\Uuid;
use ZipArchive;

class ExportLineasOsiptelMsisdnDni extends BaseExportLineas
{
    private $repo;
    private $exportService;
    // private $storage;
    private $temp_storage_path;
    private $limit_to_paginate = 1000000;

    public function __construct(LineasMTCOsiptelRepository $repo, ExportService $exportService, StorageService $storage)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->storage = $storage->getStorageSystemByName(StorageSystemName::LOCAL);
        $this->temp_storage_path = storage_path('app/public');
    }

    public function __invoke($ticket, $tipo_reporte, $step = "1")
    {
        $path = "lineas-mtc-osiptel/osiptel-msisdn-dni";
        $filename = "";
        $str_today = (new DateTime())->format("Y_m_d");
        $now = (new DateTime())->format("YmdHis");
        $count = $this->repo->countLineasOsiptelMsisdn($ticket,$tipo_reporte);
        if($tipo_reporte == "POSTPAGO"){
            $filename = "SMS_POSTPAGO_{$str_today}";
            if($count > 0){
                if($step === "1"){
                    $this->repo->saveLineasOsiptelLog($str_today, $ticket, $tipo_reporte, '', $filename, $now);
                    return $this->generateTxtFile($ticket, $tipo_reporte, "{$path}", $filename, $count, 1);
                }
            }else{
                $this->repo->saveLineasOsiptelMsisdn($ticket,$tipo_reporte);
                $count = $this->repo->countLineasOsiptelMsisdnV2($ticket,$tipo_reporte);
            }

            if($count <= 5000000){
                throw new Exception("ERROR EN LA EXTRACCION, LA CANTIDAD DE REGISTROS OBTENIDOS NO SUPERARON 
                    EL UMBRAL CORRECTO PARA LAS LINEAS POSTPAGO.
                    FAVOR DE COMUNICARSE CON AREA DE ANALITICA - JOEL MALLQUI TABOADA");
            }else{
                $this->repo->saveLineasOsiptelMsisdnV2($ticket,$tipo_reporte);
                $this->repo->saveLineasOsiptelHist($ticket,$tipo_reporte);
                $this->repo->saveLineasOsiptelLog($str_today, $ticket, $tipo_reporte, '', $filename, $now);
                return $this->generateTxtFile($ticket, $tipo_reporte, "{$path}", $filename, $count, 0);
            }

        }
        if($tipo_reporte == "PREPAGO") {
            $filename = "SMS_PREPAGO_{$str_today}";
            if($count > 0){
                if($step === "1"){
                    $this->repo->saveLineasOsiptelLog($str_today, $ticket, $tipo_reporte, '', $filename, $now);
                    return $this->generateTxtFile($ticket, $tipo_reporte, "{$path}", $filename, $count, 1);
                }
            }else{
                $count = $this->repo->countLineasOsiptelMsisdn($ticket,'POSTPAGO');
                if($count <= 5000000){
                    throw new Exception("GENERAR PRIMERO LA BASE DE POSTPAGO PARA ESTA NUEVA CARTA.");
                }else{
                    $this->repo->saveLineasOsiptelMsisdn($ticket,$tipo_reporte);
                    $count = $this->repo->countLineasOsiptelMsisdnV2($ticket,$tipo_reporte);

                    if($count <= 9500000){
                        throw new Exception("ERROR EN LA EXTRACCION, LA CANTIDAD DE REGISTROS OBTENIDOS NO SUPERARON 
                        EL UMBRAL CORRECTO PARA LAS LINEAS PREPAGO.
                        FAVOR DE COMUNICARSE CON AREA DE ANALITICA - JOEL MALLQUI TABOADA");
                    }else{
                        try{
                            $this->repo->saveLineasOsiptelMsisdnV2($ticket,$tipo_reporte);
                            $this->repo->saveLineasOsiptelHist($ticket,$tipo_reporte);
                            $this->repo->saveLineasOsiptelLog($str_today, $ticket, $tipo_reporte, '', $filename, $now);
                            return $this->generateTxtFile($ticket, $tipo_reporte, "{$path}", $filename, $count, 0);
                        }catch (Throwable $e) {
                            return $e;
                        }
                    }
                }
            }
        }

        $this->deleteOldFiles(
            $path,
            str_replace("_{$ticket}_{$str_today}","", $filename),
            "{$ticket}_{$str_today}.zip"
        );
        
    }

    private function generateTxtFile($ticket, $tipo_reporte, $path, $filename, $countLineas, $exist)
    {
        $pagination = $this->paginate($countLineas, $this->limit_to_paginate);

        $callback = function () use($ticket,$tipo_reporte,$pagination){
            $stream = fopen('php://output', 'w');

            for ($i=1; $i <= $pagination['pages']; $i++) {
                $range = $pagination['ranges'][(string) $i];
                $data = $this->getData(
                    $ticket,
                    $tipo_reporte,
                    $range['min'] === 1 ? 0 : $range['min'],
                    $this->limit_to_paginate
                );

                fwrite($stream, "SUBSCRIPTION_ACCESS_NUMBER,ID_CARD_VALUE".PHP_EOL);
                foreach ($data as $registro) {
                    // Aquí puedes formatear y escribir cada fila de datos en el archivo
                    $linea = implode(',', (array)$registro);
                    fwrite($stream, $linea . PHP_EOL);
                }
            }
            fclose($stream);
        };

        $headers = [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        if($exist){
            $mensaje = "YA SE REALIZÓ LA DESCARGA $tipo_reporte PARA ESTA CARTA $ticket";
        }else{
            $mensaje = null;
        }

        Session::flash('mensaje',$mensaje);

        return Response::stream($callback, 200, $headers);
    }

    private function getData($ticket, $tipo_reporte, $offset, $limit)
    {
        return $this->repo->getReporteOsiptelMsisdn($ticket,$tipo_reporte,$offset, $limit);
    }
}
