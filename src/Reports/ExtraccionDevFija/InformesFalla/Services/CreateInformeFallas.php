<?php

namespace AMovil\Reports\ExtraccionDevFija\InformesFalla\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFallasRepository;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use DateTime;
use Exception;

class CreateInformeFallas
{
    private $repo;
    private $extraccionFijaRepo;
    private $storage;
    private $authService;
    private $notifyUsers;
    private $baseStoragePath = "/space/reportes/informes_falla_fija";

    public function __construct(
        InformeFallasRepository $repo,
        ExtraccionDevFijaRepository $extraccionFijaRepo,
        AuthService $authService,
        StorageService $storageService,
        NotifyUsersOnInformeFallasLoaded $notifyUsers
    ) {
        $this->repo = $repo;
        $this->extraccionFijaRepo = $extraccionFijaRepo;
        $this->authService = $authService;
        $this->storage = $storageService->getStorageSystemByName(StorageSystemName::LOCAL2);
        $this->notifyUsers = $notifyUsers;
    }

    public function __invoke(string $numReporte, $file, array $planos, array $serviciosAfectados)
    {
        $filename = $numReporte.'_'.$file->getClientOriginalName();
        $tempFilePath = $file->getPathname();

        $filters = ["numero_reporte.eq.{$numReporte}"];
        $result = $this->repo->getByCriteria($filters);
        if(count($result["data"]) > 0){
            throw new Exception("El informe de fallas '{$numReporte}' ya existe");
        }
        
        $this->validateDetallePlanos($planos);
        $this->validateServiciosAfectados($serviciosAfectados);

        $username = $this->authService->getUserIdentifier();

        $this->repo->createInformeFallas($numReporte, $filename, $username, $serviciosAfectados);
        foreach($planos as $row){
            // $dtFechaIni = DateTime::createFromFormat("Y-m-d H:i:s", $row["fechaIni"]." ".$row["horaIni"]);
            // $dtFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", $row["fechaFin"]." ".$row["horaFin"]);
            $planos = str_replace(["\n", "\r", " "], ["", "", ""], $row["planos"]);
            $departamento = trim(str_replace(["\r"], [""], $row["departamento"]));
            $provincia = trim(str_replace(["\r"], [""], $row["provincia"]));
            $distrito = trim(str_replace(["\r"], [""], $row["distrito"]));

            $this->extraccionFijaRepo->createPlanoInput(
                trim($numReporte),
                $departamento,
                $provincia,
                $distrito,
                explode(",", $planos)
            );
        }

        foreach($serviciosAfectados as $row){
            $dtFechaIni = DateTime::createFromFormat("Y-m-d H:i:s", $row["fechaIni"]." ".$row["horaIni"]);
            $dtFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", $row["fechaFin"]." ".$row["horaFin"]);
            $this->extraccionFijaRepo->createServicioAfectadoInput(
                trim($numReporte),
                $username,
                $row["servicioAfectadoId"],
                $dtFechaIni,
                $dtFechaFin,
                12
            );
        }
        
        /*
        $this->repo->createInformeFallas($numReporte, $filename, $username);
        foreach($detallesExtraccion as $row){
            $dtFechaIni = DateTime::createFromFormat("Y-m-d H:i:s", $row["fechaIni"]." ".$row["horaIni"]);
            $dtFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", $row["fechaFin"]." ".$row["horaFin"]);
            $planos = str_replace(["\n", "\r", " "], ["", "", ""], $row["planos"]);
            $departamento = trim(str_replace(["\r"], [""], $row["departamento"]));
            $provincia = trim(str_replace(["\r"], [""], $row["provincia"]));
            $distrito = trim(str_replace(["\r"], [""], $row["distrito"]));

            $this->extraccionFijaRepo->createInput(
                trim($numReporte),
                $username,
                $row["servicioAfectado"],
                $dtFechaIni,
                $dtFechaFin,
                12,
                $departamento,
                $provincia,
                $distrito,
                explode(",", $planos),
            );
        }*/

        $this->storage->put("{$this->baseStoragePath}/{$filename}", file_get_contents($tempFilePath));
        
        foreach($serviciosAfectados as $row){
            $this->notifyUsers->__invoke($numReporte, $row["servicioAfectadoId"]);
        }
    }

    public function validateDetallesExtraccion(array $detallesExtraccion)
    {
        $distritosByName = [];
        foreach($detallesExtraccion as $row){
            $key = $row["provincia"]."_".$row["distrito"];
            if(array_key_exists($key, $distritosByName)){
                throw new Exception("No se puede agregar el mismo distrito mas de una vez");
            }
            $dtFechaIni = DateTime::createFromFormat("Y-m-d H:i:s", $row["fechaIni"]." ".$row["horaIni"]);
            $dtFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", $row["fechaFin"]." ".$row["horaFin"]);
            if(!$dtFechaIni){
                throw new Exception("La fecha '". $row["fechaIni"]." ".$row["horaIni"] ."' no es valida");
            }
            if(!$dtFechaFin){
                throw new Exception("La fecha '". $row["fechaFin"]." ".$row["horaFin"] ."' no es valida");
            }
            $distritosByName[$key] = $row;
        }
    }

    public function validateDetallePlanos(array $planos)
    {
        $distritosByName = [];
        foreach($planos as $row){
            $key = $row["provincia"]."_".$row["distrito"];
            if(array_key_exists($key, $distritosByName)){
                throw new Exception("No se puede agregar el mismo distrito mas de una vez");
            }
            $distritosByName[$key] = $row;
        }
    }

    public function validateServiciosAfectados(array $serviciosAfectados)
    {
        foreach($serviciosAfectados as $row){
            $dtFechaIni = DateTime::createFromFormat("Y-m-d H:i:s", $row["fechaIni"]." ".$row["horaIni"]);
            $dtFechaFin = DateTime::createFromFormat("Y-m-d H:i:s", $row["fechaFin"]." ".$row["horaFin"]);
            if(!$dtFechaIni){
                throw new Exception("La fecha '". $row["fechaIni"]." ".$row["horaIni"] ."' no es valida");
            }
            if(!$dtFechaFin){
                throw new Exception("La fecha '". $row["fechaFin"]." ".$row["horaFin"] ."' no es valida");
            }
            if($dtFechaIni > $dtFechaFin){
                throw new Exception("La fecha y hora de inicio no puede ser mayor a la fecha y hora final");
            }
        }
    }
}
