<?php

namespace AMovil\Reports\General\LineasMTCOsiptel\Services;

use AMovil\Reports\General\LineasMTCOsiptel\Domain\LineasMTCOsiptelRepository;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Ramsey\Uuid\Uuid;
use ZipArchive;

class CargarSMSLog
{
    private $repo;
    private $storage;
    private $storage_path;

    public function __construct(LineasMTCOsiptelRepository $repo, StorageService $storage)
    {
        $this->repo = $repo;
        $this->storage = $storage->getStorageSystemByName(StorageSystemName::LOCAL2);
        $this->storage_path = storage_path('app/public');
    }

    public function __invoke($tipo_plan, $tipo_solicitud, $ticket, $file)
    {
        $original_filename = $file->getClientOriginalName();
        $fecha = explode("<>", $ticket)[0];
        $ticket = explode("<>", $ticket)[1];
        $ticket = str_replace(" ", "_", $ticket);
        $now = (new DateTime())->format("YmdHis");
        $filename = "/space/lineas_sms_mtc_osiptel/{$now}_{$ticket}_{$original_filename}";

        // $data = $this->getDataFromExcel($file);
        if(!copy($file->getPathname(), $filename)){
            throw new Exception("No se pudo cargar el archivo a /space/lineas_sms_mtc_osiptel");
        }
        try {
            $this->repo->saveDataLog($fecha, $ticket, $tipo_plan, $tipo_solicitud, $filename);
    
            $zipFilename = str_replace(".csv", ".zip", $filename);
            $zip = new ZipArchive();
            $zip->open($zipFilename, ZipArchive::CREATE);
            $zip->addFile($filename, "{$now}_{$ticket}_{$original_filename}");
            $zip->close();
    
            // $this->storage->put(str_replace(".csv", ".zip", $filename), file_get_contents($zipFilename));
            // unlink($zipFilename);
            unlink($filename);
    
            $this->repo->saveLog($fecha, $ticket, $tipo_plan, $tipo_solicitud, $filename, $now);
        } catch (\Throwable $th) {
            unlink($filename);
            throw $th;
        }
    }

    public function getDataFromExcel($excel)
    {
        $values = [];
        if ($excel !== null) {
            $reader = IOFactory::createReader('Csv');
            $spreedsheet = $reader->load($excel->getPathname());
            $sheet = $spreedsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            for ($i=2; $i <= $highestRow; $i++) {
                $row = [
                    $sheet->getCellByColumnAndRow(1, $i)->getValue(),
                    $sheet->getCellByColumnAndRow(2, $i)->getValue(),
                    $sheet->getCellByColumnAndRow(3, $i)->getValue(),
                    $sheet->getCellByColumnAndRow(4, $i)->getValue(),
                    $sheet->getCellByColumnAndRow(5, $i)->getValue(),
                    $sheet->getCellByColumnAndRow(6, $i)->getValue(),
                    $sheet->getCellByColumnAndRow(7, $i)->getValue(),
                    $sheet->getCellByColumnAndRow(8, $i)->getValue(),
                    $sheet->getCellByColumnAndRow(9, $i)->getValue(),
                    $sheet->getCellByColumnAndRow(10, $i)->getValue(),
                    $sheet->getCellByColumnAndRow(11, $i)->getValue(),
                    $sheet->getCellByColumnAndRow(12, $i)->getValue(),
                    $sheet->getCellByColumnAndRow(13, $i)->getValue(),
                    $sheet->getCellByColumnAndRow(14, $i)->getValue(),
                    $sheet->getCellByColumnAndRow(15, $i)->getValue(),
                    $sheet->getCellByColumnAndRow(16, $i)->getValue(),
                ];
                if($row[0] !== ""){
                    $row[0] = substr(str_replace("T", " ", $row[0]), 0, 19);
                }
                if($row[1] !== ""){
                    $row[1] = substr(str_replace("T", " ", $row[1]), 0, 19);
                }
                $values[] = $row;
            }
        }
        return $values;
    }
}
