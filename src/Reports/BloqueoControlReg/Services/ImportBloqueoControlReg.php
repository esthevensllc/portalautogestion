<?php

namespace AMovil\Reports\BloqueoControlReg\Services;

use AMovil\Reports\BloqueoControlReg\Domain\BloqueoControlRegRepository;
use AMovil\Reports\BloqueoControlReg\Domain\BloqueoControlRegLogRepository;
use AMovil\Reports\BloqueoControlReg\Domain\TipoDocumento;
use AMovil\Reports\BloqueoControlReg\Domain\TipoOperacion;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Ramsey\Uuid\Uuid;

class ImportBloqueoControlReg
{
    private $logRepo;
    private $repo;
    private $localStorage;
    private $eirStorage;
    private $localBaseStoragePath = "/space/reportes/bloqueo_control_regulatorio";
    private $baseStoragePath = "/comptel/BATCH/DWH/pre_input";
    private $notifyUsers;
    private $notifyUsersTipifi;

    public function __construct(
        BloqueoControlRegLogRepository $logRepo,
        BloqueoControlRegRepository $repo,
        StorageService $storageService,
        NotifyUsersOnInformeBloqDesbloqLoaded $notifyUsers,
        NotifyUsersOnInformeBloqDesbloqTipifi $notifyUsersTipifi
    ) {
        $this->logRepo = $logRepo;
        $this->repo = $repo;
        $this->localStorage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
        $this->eirStorage = $storageService->getStorageSystemByName(StorageSystemName::EIR);
        $this->notifyUsers = $notifyUsers;
        $this->notifyUsersTipifi = $notifyUsersTipifi;
    }

    public function __invoke($tipoOperacionId, $tipoDocumentoId, $documento, $imei, $tipificacionId, $ticklerId, $instantaneo, $notas)
    {
        $tempFilePath = $documento->getPathname();
        $originalFilename = $documento->getClientOriginalName();
        $extension = $documento->getClientOriginalExtension();
        $newId = str_replace([".xlsx", ".pdf"], ["", ""], $originalFilename);

        if(!(TipoOperacion::idIsBloqueo($tipoOperacionId) || TipoOperacion::idIsDesbloqueo($tipoOperacionId))){
            throw new Exception("El tipo de operación no es valido");
        }

        $documentos = $this->logRepo->getByCriteria([["filename", $originalFilename]]);
        if(count($documentos) > 0){
            throw new Exception("El documento '$originalFilename' ya fue cargado anteriormente");
        }

        $tempEIRFilePath = null;
        $strNow = (new DateTime())->format("YmdHis");
        $tempEIRFilename = "ILBATCH.dat.{$strNow}1.PROV_REGULATORIO_IMEI";
        $cantRegistros = 0;
        $cantUnicos = 0;
        if (TipoDocumento::idIsSIBMED($tipoDocumentoId)) {
            if($extension !== "xlsx"){
                throw new Exception("El formato '{$extension}' del documento no es valido");
            }
            $fileValues = $this->getDataFromFile($tempFilePath);
            $cantRegistros = count($fileValues);
            $cantUnicos = $this->countImeisUnicos($fileValues);
            $this->validateFileData($fileValues);
            $this->repo->saveReporteSIBMED($newId, $fileValues);
            $tempEIRFilePath = $this->generateEIRFile($tipoOperacionId, $fileValues);
        } else if (TipoDocumento::idIsDAPU($tipoDocumentoId)) {
            if($extension !== "pdf"){
                throw new Exception("El formato '{$extension}' del documento no es valido");
            }
            $fileValues = [["imei" => trim($imei)]];
            $cantRegistros = 1;
            $cantUnicos = 1;
            $this->validateFileData($fileValues);
            $this->repo->saveReporteDAPU($newId, $tempFilePath, $imei);
            $tempEIRFilePath = $this->generateEIRFile($tipoOperacionId, $fileValues);
        } else {
            throw new Exception("El tipo de documento no es valido");
        }
        // $sizeBytes = $this->storage->size("{$this->baseStoragePath}/{$tempEIRFilename}");
        $sizeBytes = filesize($tempFilePath);
        $this->logRepo->saveLog(
            $newId,
            $tipoOperacionId,
            $tipoDocumentoId,
            $originalFilename,
            $sizeBytes,
            $tempFilePath,
            $tempEIRFilename,
            $cantRegistros,
            $cantUnicos
        );

        $this->localStorage->put("{$this->localBaseStoragePath}/{$tempEIRFilename}", file_get_contents($tempEIRFilePath));
        $this->eirStorage->put("{$this->baseStoragePath}/{$tempEIRFilename}", file_get_contents($tempEIRFilePath));
        unlink($tempEIRFilePath);

        $report = $this->logRepo->findFileContentById($newId);

        $this->notifyUsers->__invoke($report);

        $this->notifyUsersTipifi->__invoke($tipoOperacionId, $tipoDocumentoId, $originalFilename, $imei, $tipificacionId, $ticklerId, $instantaneo, $notas);
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
                "imei" => trim($sheet->getCellByColumnAndRow(3, $i)->getValue()),
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

    private function countImeisUnicos($fileValues)
    {
        $uniqueValues = [];
        foreach($fileValues as $row){
            $uniqueValues[$row["imei"]] = $row;
        }
        return count($uniqueValues);
    }

    private function validateFileData($data){
        foreach($data as $row){
            $imei = $row["imei"];
            if(!is_numeric($imei)){
                throw new Exception("El imei '{$imei}' no es valido");
            }
        }
    }

    private function generateEIRFile($tipoOperacionId, $imeis)
    {
        $eirOperation = "";
        if(TipoOperacion::idIsBloqueo($tipoOperacionId)){
            $eirOperation = "BIMEI";
        }else if (TipoOperacion::idIsDesbloqueo($tipoOperacionId)){
            $eirOperation = "AIMEI";
        }
        $tempFilename = sys_get_temp_dir() ."/phpexport-". Uuid::uuid4()->toString().".tmp";
        $file = fopen($tempFilename, "w");
        foreach($imeis as $row){
            $imei = substr($row["imei"], 0, 14);
            fwrite($file, "{$eirOperation},0,5,0,0,0,0,0,0,0,0,0,0,0,DWH,{$imei},0,0,0,0,0,0,,1".PHP_EOL);
        }
        fclose($file);
        return $tempFilename;
    }
}
