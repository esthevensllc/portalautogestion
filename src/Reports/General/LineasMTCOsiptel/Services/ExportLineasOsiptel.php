<?php

namespace AMovil\Reports\General\LineasMTCOsiptel\Services;

use AMovil\Reports\General\LineasMTCOsiptel\Domain\LineasMTCOsiptelRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use DateTime;
use Exception;
use Ramsey\Uuid\Uuid;
use ZipArchive;

class ExportLineasOsiptel extends BaseExportLineas
{
    private $repo;
    private $exportService;
    // protected $storage;
    private $temp_storage_path;
    private $limit_to_paginate = 1000000;

    public function __construct(LineasMTCOsiptelRepository $repo, ExportService $exportService, StorageService $storage)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->storage = $storage->getStorageSystemByName(StorageSystemName::LOCAL);
        $this->temp_storage_path = storage_path('app/public');
    }

    public function __invoke($tipo_reporte, $step = "1"): Response
    {
        $path = "lineas_mtc_osiptel";
        $filename = "";
        $str_today = (new DateTime())->format("Ymd");
        if($tipo_reporte === "1"){
            $filename = "BASE_SMS_POSTPAGO_OSIPTEL_{$str_today}";
            $count = $this->repo->countLineasPostpagoOsiptelForToday();
            if($count > 0){
                if($step === "1"){
                    throw new Exception("Ya se inserto las lineas postpago para hoy");
                }
            }else{
                $this->repo->saveLineasPostpagoOsiptelForToday();
                $count = $this->repo->countLineasPostpagoOsiptelForToday();
            }

            if($count < 4000000){
                throw new Exception("La cantidad de lineas postpago es menor a 4 millones");
            }

            if(!$this->storage->exists("{$path}/{$filename}.zip")){
                $this->generateZipFile($tipo_reporte, "{$path}", $filename, $count);
            }
        }else{
            $filename = "BASE_SMS_PREPAGO_OSIPTEL_{$str_today}";
            $count = $this->repo->countLineasPrepagoOsiptelForToday();
            if($count > 0){
                if($step === "1"){
                    throw new Exception("Ya se inserto las lineas prepago para hoy");
                }
            }else{
                $this->repo->saveLineasPrepagoOsiptelForToday();
                $count = $this->repo->countLineasPrepagoOsiptelForToday();
            }
            $countPostpago = $this->repo->countLineasPostpagoOsiptelForToday();
            if($countPostpago === 0){
                throw new Exception("Aun no se ejecuta las extracción de las lineas postpago");
            }
            if($count < 9400000){
                throw new Exception("La cantidad de lineas prepago es menor a 9 millones");
            }
            if(!$this->storage->exists("{$path}/{$filename}.zip")){
                $this->generateZipFile($tipo_reporte, "{$path}", $filename, $count);
            }
        }

        $this->deleteOldFiles($path);
        
        return new Response([], [
            "filename" => "{$filename}.zip",
            "type" => "zip",
            "content" => $this->storage->get("{$path}/{$filename}.zip")
        ]);
    }

    private function getData($tipo_reporte, $offset, $limit)
    {
        if($tipo_reporte === "1"){
            return $this->repo->getLineasPostpagoOsiptel($offset, $limit);
        }else{
            return $this->repo->getLineasPrepagoOsiptel($offset, $limit);
        }
    }

    private function generateZipFile($tipo_reporte, $path, $filename, $countLineas)
    {
        $str_time = (new DateTime())->format("YmdHis");
        $tempFilenames = [];
        $zipFilename = "{$this->temp_storage_path}/{$str_time}_{$filename}.zip";
        $headers = ["msisdn" => ["label" => "MSISDN"]];

        $zip = new ZipArchive();
        $zip->open($zipFilename, ZipArchive::CREATE);

        /*
        $pagination = $this->paginate($countLineas, $this->limit_to_paginate);
        for ($i=1; $i <= $pagination['pages']; $i++) {
            $range = $pagination['ranges'][(string) $i];
            $data = $this->getData(
                $tipo_reporte,
                $range['min'] === 1 ? 0 : $range['min'],
                $this->limit_to_paginate
            );

            $tempFilename = "{$this->temp_storage_path}/".Uuid::uuid4()->toString().".csv";
            $file = fopen($tempFilename, "w");
            fwrite($file, "SUBSCRIPTION_ACCESS_NUMBER".PHP_EOL);
            foreach($data as $row){
                fwrite($file, $row->msisdn.PHP_EOL);
            }
            fclose($file);
            $zip->addFile($tempFilename, "{$filename}_{$i}.txt");

            $tempFilenames[] = $tempFilename;
        }*/

        $data = $this->getData($tipo_reporte, null, null);

        $tempFilename = "{$this->temp_storage_path}/".Uuid::uuid4()->toString().".csv";
        $file = fopen($tempFilename, "w");
        fwrite($file, "SUBSCRIPTION_ACCESS_NUMBER".PHP_EOL);
        foreach($data as $row){
            fwrite($file, $row->msisdn.PHP_EOL);
        }
        fclose($file);
        $zip->addFile($tempFilename, "{$filename}.txt");
        $tempFilenames[] = $tempFilename;

        $zip->close();
        $tempFilenames[] = $zipFilename;

        $content = file_get_contents($zipFilename);
        
        foreach($tempFilenames as $file){
            unlink($file);
        }

        $this->storage->put("{$path}/{$filename}.zip", $content);
        return "{$path}/{$filename}.zip";
    }
    
}
