<?php

namespace AMovil\Reports\ReportLog\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Auth\User\Domain\UserRepository;
use AMovil\Reports\ReportLog\Domain\ReportLogRepository;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use DateTime;

class SaveReportLog
{
    private $repo;
    private $storage;
    private $authService;
    private $userRepository;
    private $base_path1 = "/space/reportes";
    private $base_path2 = "/space/reportes";

    public function __construct(ReportLogRepository $repo, StorageService $storage, AuthService $authService, UserRepository $userRepository)
    {
        $this->repo = $repo;
        $this->storage = $storage;
        $this->authService = $authService;
        $this->userRepository = $userRepository;
        // $this->base_path = "/space/reportes/";
    }

    public function __invoke(array $log_data, ?string $local_file, string $path)
    {
        $userIdentifier = $this->authService->getUserIdentifier();
        $user = $this->userRepository->findByIdentifier($userIdentifier);
        $contacto = null;
        $direccion = null;
        $area = null;
        if($user !== null){
            $contacto = "{$user->name} {$user->last_name}";
            $direccion = $user->direccion;
            $area = $user->area;
        }

        $file = null;
        if(array_key_exists("filename", $log_data)){
            $file = $log_data["filename"];
            //$file_parts = explode("/", $local_file);
            //$file = $file_parts[count($file_parts)-1];
        }

        if($log_data['mensaje']??null !== null){
            if(strlen($log_data['mensaje']) >= 3000){
                $log_data['mensaje'] = substr($log_data['mensaje'], 0, 3000);
            }
        }

        $this->repo->save(
            $log_data['name'],
            $log_data['direccion'] ?? $direccion,
            $log_data['area'] ?? $area,
            $log_data['contacto'] ?? $contacto,
            $userIdentifier,
            $log_data['responsable'] ?? "DIEGO MORENO",
            $file,
            DateTime::createFromFormat("Y-m-d H:i:s", $log_data['ini']),
            DateTime::createFromFormat("Y-m-d H:i:s", $log_data['fin']),
            0,
            array_key_exists("estado", $log_data) ? $log_data["estado"] : ($local_file !== null ? 1 : 0),
            $log_data['mensaje'] ?? null,
            $log_data['trac_name'] ?? null
        );

        if($local_file !== null){
            $file_contents = file_get_contents($local_file);
    
            $storage = $this->storage->getStorageSystemByName(StorageSystemName::LOCAL2);
            $storage->put("{$this->base_path1}/{$path}/{$file}", $file_contents);
    
            $storage = $this->storage->getStorageSystemByName(StorageSystemName::REPORTS_LOG);
            $storage->put("{$this->base_path2}/{$path}/{$file}", $file_contents);
        }
    }

    public function fromExport(?ExportService $exportService, $log_data, ?string $filename, string $path)
    {
        $userIdentifier = $this->authService->getUserIdentifier();
        $user = $this->userRepository->findByIdentifier($userIdentifier);
        $contacto = null;
        $direccion = null;
        $area = null;
        if($user !== null){
            $contacto = "{$user->name} {$user->last_name}";
            $direccion = $user->direccion;
            $area = $user->area;
        }

        if($log_data['mensaje']??null !== null){
            if(strlen($log_data['mensaje']) >= 3000){
                $log_data['mensaje'] = substr($log_data['mensaje'], 0, 3000);
            }
        }

        $this->repo->save(
            $log_data['name'],
            $log_data['direccion'] ?? $direccion,
            $log_data['area'] ?? $area,
            $log_data['contacto'] ?? $contacto,
            $userIdentifier,
            $log_data['responsable'] ?? "DIEGO MORENO",
            "{$filename}.csv",
            DateTime::createFromFormat("Y-m-d H:i:s", $log_data['ini']),
            DateTime::createFromFormat("Y-m-d H:i:s", $log_data['fin']),
            0,
            $exportService !== null ? 1 : 0,
            $log_data['mensaje'] ?? null,
            $log_data['trac_name'] ?? null
        );

        if($exportService !== null){

            $file_contents = $exportService->getWriter(WriterType::CSV)->getOutput();
    
            $storage = $this->storage->getStorageSystemByName(StorageSystemName::LOCAL2);
            $storage->put("{$this->base_path1}/{$path}/{$filename}.csv", $file_contents);
    
            $storage = $this->storage->getStorageSystemByName(StorageSystemName::REPORTS_LOG);
            $storage->put("{$this->base_path2}/{$path}/{$filename}.csv", $file_contents);
        }
    }
}
