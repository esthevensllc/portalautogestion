<?php

namespace AMovil\Reports\BajaPrepago\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\BajaPrepago\Domain\BajaPrepagoRepository;
use AMovil\Reports\BajaPrepago\Domain\BajaPrepagoStatus;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use DateTime;
use Exception;
use Ramsey\Uuid\Uuid;

class ImportBajasPrepago
{
    private $repository;
    private $authService;

    public function __construct(BajaPrepagoRepository $repository, AuthService $authService)
    {
        $this->repository = $repository;
        $this->authService = $authService;
    }

    public function __invoke($clase, $subClase, $notas, FileInput $fileInput, $ipAddress): Response
    {
        $validateResponse = $this->validateLineas($fileInput);
        if ($validateResponse->fails()) {
            return $validateResponse;
        }
        $baseId = Uuid::uuid7()->toString();

        $this->repository->insertPreimport($this->getFileDataToImport($fileInput, $baseId, $clase, $subClase, $notas, $this->authService->getUserIdentifier()));
        $this->repository->validatePreimport($baseId);
        $summary = $this->repository->getPreimportSummary($baseId);

        return Response::respData([
            "baseId" => $baseId,
            "summary" => $summary
        ]);
    }

    public function insertFinal($baseId, $ipAddress){
        $summary = $this->repository->getPreimportSummary($baseId);
        $summaryRecord = null;
        foreach ($summary as $row) {
            if ($row->flag_validacion === BajaPrepagoStatus::VALIDADO) {
                $summaryRecord = $row;
                break;
            }
        }

        if ($summaryRecord === null) {
            return Response::respError(["message" => "No se puede cargar porque no se encontró lineas validadas"]);
        }

        if ($summaryRecord->username !== $this->authService->getUserIdentifier()) {
            return Response::respError(["message" => "Se debe usar el mismo usuario para importar la base final"]);
        }

        $importLog = $this->repository->findLogByBaseId($baseId);
        if ($importLog !== null) {
            return Response::respError(["message" => "No se puede procesar dos veces la misma base"]);
        }

        $this->repository->insertFromPreimport($baseId);

        $this->repository->createLog(
            $baseId,
            $summaryRecord->clase,
            $summaryRecord->subclase,
            $summaryRecord->notas,
            $this->authService->getUserIdentifier(),
            $summaryRecord->cantidad,
            $ipAddress
        );
        return Response::respData();
    }

    private function validateLineas(FileInput $fileInput): Response {
        $lineas = $this->getFileData($fileInput);
        $response = null;
        $cantLineas = 0;
        foreach ($lineas as $linea) {
            $cantLineas += 1;
            if (!is_numeric($linea)) {
                $response = Response::respError(["message" => "La linea {$linea} no es un número valido"]);
                break;
            }
            if (strlen($linea) !== 11) {
                $response = Response::respError(["message" => "La linea {$linea} no es válida porque no tiene 11 caracteres"]);
                break;
            }
            if (strlen($linea) === 0) {
                $response = Response::respError(["message" => "La linea vacia en la fila {$cantLineas} no es válida"]);
                break;
            }
        }
        if ($cantLineas === 0) {
            $response = Response::respError(["message" => "Se debe cargar como minimo una linea"]);
        }
        return $response === null ? Response::respData(["cantLineas" => $cantLineas]) : $response;
    }

    private function getFileDataToImport(FileInput $fileInput, $baseId, $clase, $subClase, $notas, $username){
        $now = new DateTime();
        $lineas = $this->getFileData($fileInput);
        foreach ($lineas as $linea) {
            yield [
                "BASE_ID" => $baseId,
                "MSISDN" => $linea,
                "FECHA_CARGA" => $now,
                "CLASE" => $clase,
                "SUBCLASE" => $subClase,
                "NOTAS" => $notas,
                "USERNAME" => $username,
            ];
        }
    }

    private function getFileData(FileInput $fileInput) {
        $file = fopen($fileInput->getFilePath(), "r");
        if ($file) {
            while (($row = fgets($file)) !== false) {
                $value = trim($row);
                yield $value;
            }
            fclose($file);
        } else {
            throw new Exception("No se puede leer el archivo");
        }
    }
}
