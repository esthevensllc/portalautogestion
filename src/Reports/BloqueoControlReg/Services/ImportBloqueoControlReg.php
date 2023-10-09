<?php

namespace AMovil\Reports\BloqueoControlReg\Services;

use AMovil\Reports\BloqueoControlReg\Domain\BloqueoControlRegRepository;
use AMovil\Reports\BloqueoControlReg\Domain\BloqueoControlRegLogRepository;
use AMovil\Reports\BloqueoControlReg\Domain\TipoDocumento;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportBloqueoControlReg
{
    private $logRepo;
    private $repo;
    private $storage;
    private $baseStoragePath = "/space/reportes/bloqueo_control_regulatorio";

    public function __construct(
        BloqueoControlRegLogRepository $logRepo,
        BloqueoControlRegRepository $repo,
        StorageService $storageService
    ) {
        $this->logRepo = $logRepo;
        $this->repo = $repo;
        $this->storage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
    }

    public function __invoke($tipoDocumentoId, $documento, $imei)
    {
        $tempFilePath = $documento->getPathname();
        $originalFilename = $documento->getClientOriginalName();
        $extension = $documento->getClientOriginalExtension();
        // $newId = $this->getNewId();
        $newId = str_replace([".xlsx", ".pdf"], ["", ""], $originalFilename);

        $documentos = $this->logRepo->getByCriteria([["filename", $originalFilename]]);
        if(count($documentos) > 0){
            throw new Exception("El documento '$originalFilename' ya fue cargado anteriormente");
        }

        if (TipoDocumento::idIsSIBMED($tipoDocumentoId)) {
            if($extension !== "xlsx"){
                throw new Exception("El formato '{$extension}' del documento no es valido");
            }
            $fileValues = $this->getDataFromFile($tempFilePath);
            $this->repo->saveReporteSIBMED($newId, $fileValues);
        } else if (TipoDocumento::idIsDAPU($tipoDocumentoId)) {
            if($extension !== "pdf"){
                throw new Exception("El formato '{$extension}' del documento no es valido");
            }
            $this->repo->saveReporteDAPU($newId, $tempFilePath, $imei);
        } else {
            throw new Exception("El tipo de documento no es valido");
        }
        $this->storage->put("{$this->baseStoragePath}/{$originalFilename}", file_get_contents($tempFilePath));
        $sizeBytes = $this->storage->size("{$this->baseStoragePath}/{$originalFilename}");
        $this->logRepo->saveLog($newId, $tipoDocumentoId, $originalFilename, $sizeBytes);
    }

    private function getNewId(): string {
        return (new DateTime())->format("YmdHis");
    }
    
    private function getDataFromFile($filepath)
    {
        $values = [];
        $reader = IOFactory::createReader('Xlsx');
        $spreedsheet = $reader->load($filepath);
        $sheet = $spreedsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        for ($i=2; $i <= $highestRow; $i++) {
            $row = [
                "empresa" => $sheet->getCellByColumnAndRow(1, $i)->getValue(),
                "registro" => $sheet->getCellByColumnAndRow(2, $i)->getValue(),
                "imei" => $sheet->getCellByColumnAndRow(3, $i)->getValue(),
                "nombres_apellidos_razon" => $sheet->getCellByColumnAndRow(4, $i)->getValue(),
                "tipo_documento" => $sheet->getCellByColumnAndRow(5, $i)->getValue(),
                "numero_documento" => $sheet->getCellByColumnAndRow(6, $i)->getValue(),
                "n_servicio_movil" => $sheet->getCellByColumnAndRow(7, $i)->getValue(),
                "accion" => $sheet->getCellByColumnAndRow(8, $i)->getValue(),
                "motivacion" => $sheet->getCellByColumnAndRow(9, $i)->getValue(),
                "fecha_registro" => $sheet->getCellByColumnAndRow(10, $i)->getValue(),
                "fecha_limite" => $sheet->getCellByColumnAndRow(11, $i)->getValue(),
                "acreditacion" => $sheet->getCellByColumnAndRow(12, $i)->getValue(),
                "situacion" => $sheet->getCellByColumnAndRow(13, $i)->getValue(),
            ];
            $values[] = $row;
        }
        return $values;
    }
}
