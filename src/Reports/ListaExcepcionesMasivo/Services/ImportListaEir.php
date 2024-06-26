<?php

namespace AMovil\Reports\ListaExcepcionesMasivo\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ListaExcepcionesMasivo\Domain\ListaExcepcionesMasivoRepository;
use AMovil\Reports\ListaExcepcionesMasivo\Domain\TipoOperacion;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Ramsey\Uuid\Uuid;

class ImportListaEir
{
    private $repo;
    private $localStorage;
    private $eirStorage;
    private $authService;
    private $localBaseStoragePath = "/space/reportes/lista_excepciones_masivo_control_regulatorio";
    private $baseStoragePath = "/comptel/BATCH/DWH/pre_input";

    public function __construct(
        ListaExcepcionesMasivoRepository $repo,
        StorageService $storageService,
        AuthService $authService
    ) {
        $this->repo = $repo;
        $this->localStorage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
        $this->eirStorage = $storageService->getStorageSystemByName(StorageSystemName::EIR);
        $this->authService = $authService;
    }

    public function __invoke($tipoOperacionId, FileInput $documento): Response
    {
        $originalFilename = $documento->getFilename();

        if(!TipoOperacion::isValid($tipoOperacionId)){
            return new Response(["message" => "El tipo de operación no es valido"]);
        }

        $documentos = $this->repo->getByCriteria([["filename", $originalFilename]]);
        if(count($documentos) > 0){
            return new Response(["message" => "El documento '$originalFilename' ya fue cargado anteriormente"]);
        }

        $id = (new DateTime())->format("YmdHis");
        $strNow = (new DateTime())->format("YmdHis");
        // ILBATCH.dat.REQ000003202938_20240515000000.PROV_LISTA_EXCEPCION
        $tempEIRFilename = "ILBATCH.dat.{$strNow}.PROV_LISTA_EXCEPCION";
        
        $response = $this->validateAndGetFileData($documento);
        if($response->fails()){
            return $response;
        }

        $tempEIRFilePath = $this->generateEIRFile($tipoOperacionId, $response->data());
        $sizeBytes = filesize($documento->getFilePath());
        $this->repo->saveLog(
            $id,
            $tipoOperacionId,
            $originalFilename,
            $sizeBytes,
            $tempEIRFilename,
            $this->authService->getUserIdentifier(),
            $response->data()
        );

        $eirFileContent = file_get_contents($tempEIRFilePath);

        $this->localStorage->put("{$this->localBaseStoragePath}/{$originalFilename}", $eirFileContent);
        $this->eirStorage->put("{$this->baseStoragePath}/{$tempEIRFilename}", $eirFileContent);
        unlink($tempEIRFilePath);
        $tempEIRFilePath = null;

        return new Response([]);
    }

    private function validateAndGetFileData(FileInput $file): Response {
        if(!in_array($file->getExtension(), ["xlsx", "xls", "txt", "csv"])){
            return new Response(["message" => "El archivo no es valido solo se permiten (xlsx, xls, txt y csv)"]);
        }
        $readerType = ucfirst($file->getExtension());
        if($readerType === "Txt"){
            $readerType = "Csv";
        }
        $values = [];
        $reader = IOFactory::createReader($readerType);
        $spreedsheet = $reader->load($file->getFilePath());
        $sheet = $spreedsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        if($sheet->getHighestColumn() !== "C"){
            return new Response(["message" => "El archivo tiene que tener exactamente tres columnas"]);
        }
        for ($i=1; $i <= $highestRow; $i++) {
            $row = [
                "imei" => trim($sheet->getCell("A{$i}")->getValue()),
                "imsi" => trim($sheet->getCell("B{$i}")->getValue()),
                "linea" => trim($sheet->getCell("C{$i}")->getValue()),
            ];
            $errors = [];
            $imei = $row["imei"];
            if(!is_numeric($imei)){
                $errors[] = "El imei '{$imei}' no es valido";
            }
            $imsi = $row["imsi"];
            if(!is_numeric($imsi)){
                $errors[] = "El imsi '{$imsi}' no es valido";
            }
            $linea = $row["linea"];
            if(!is_numeric($linea)){
                $errors[] = "La linea '{$linea}' no es valida";
            }
            if(count($errors) > 0){
                return new Response(["message" => "Fila {$i}: ".implode(", ", $errors)]);
            }
            $values[] = $row;
        }
        return new Response([], $values);
    }

    private function generateEIRFile(int $tipoOperacionId, $imeis)
    {
        $eirOperation = "";
        if(TipoOperacion::INGRESAR_LISTA === $tipoOperacionId){
            $eirOperation = "ACTLISTEXCEP";
        }else if (TipoOperacion::RETIRAR_LISTA === $tipoOperacionId){
            $eirOperation = "DESLISTEXCEP";
        }
        $tempFilename = sys_get_temp_dir() ."/phpexport-". Uuid::uuid4()->toString().".tmp";
        $file = fopen($tempFilename, "w");
        foreach($imeis as $row){
            $imei = substr($row["imei"], 0, 14);
            $imsi = substr($row["imsi"], 0, 16);
            // ACTLISTEXCEP,0,5,0,0,0,716101681324200,0,0,0,0,0,0,0,DWH,01058000549434,0,0,0,0,0,0,,1
            fwrite($file, "{$eirOperation},0,5,0,0,0,{$imsi},0,0,0,0,0,0,0,DWH,{$imei},0,0,0,0,0,0,,1".PHP_EOL);
        }
        fclose($file);
        return $tempFilename;
    }
}
