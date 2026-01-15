<?php

namespace AMovil\Reports\GeoMarketing\Services;

use AMovil\Reports\GeoMarketing\Domain\GeoMarketingRepository;
use AMovil\Reports\ReportLog\Domain\ReportLogStatus;
use AMovil\Reports\ReportLog\Services\SaveReportDto;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use DateInterval;
use DateTime;
use Exception;

class ExportGeoMarketing
{
    private $baseById = [
        "1" => ["name" => "ESTADIO_NACIONAL"],
        "2" => ["name" => "ESTADIO_MONUMENTAL"],
        "3" => ["name" => "ESTADIO_MONUMENTAL_DE_LA_UNSA"],
        "4" => ["name" => "ESTACION_LOS_JARDINES"],
        "5" => ["name" => "ESTACION_VILLA_EL_SALVADOR"],
        "6" => ["name" => "GOLF_LOS_INCAS"],
        "7" => ["name" => "GOLF_SAN_ISIDRO_COUNTRY"],
        "8" => ["name" => "GOLF_LA_PLANICIE"],
        "9" => ["name" => "COUNTRY_CLUB_VILLA_DE_GOLF"],
        "10" => ["name" => "PARQUE_DE_LA_EXPOSICION_ANFITEATRO"],
        "11" => ["name" => "GRAN_TEATRO_NACIONAL"],
        "12,13,14,15" => ["name" => "MALL_PLAZA_AREQUIPA"],
        "16" => ["name" => "PLAZA NORTE"],
        "17" => ["name" => "PLAYAS_SUR"],
    ];
    private $repository;
    private $saveReportLog;
    private $storage;
    const MAX_DAYS = 30;

    public function __construct(GeoMarketingRepository $repo, SaveReportLog $saveReportLog, StorageService $storage)
    {
        $this->repository = $repo;
        $this->saveReportLog = $saveReportLog;
        $this->storage = $storage->getStorageSystemByName(StorageSystemName::LOCAL2);
    }

    public function __invoke($nintex, $base, $fechaIni, $fechaFin, $whiteList)
    {
        $fechaIniExec = new DateTime();
        $reporteInput = ["nintex" => $nintex, 'base' => $base, 'fechaInicio' => $fechaIni, 'fechaFin' => $fechaFin, 'whiteList' => $whiteList];

        try {
            $dtFechaIni = DateTime::createFromFormat("Y-m-d H:i", $fechaIni);
            $dtFechaFin = DateTime::createFromFormat("Y-m-d H:i", $fechaFin);
            $now = new DateTime();
            $diff = $dtFechaFin->getTimestamp() - $dtFechaIni->getTimestamp();
            $maxDays = self::MAX_DAYS;
            $max_diff_hours = $maxDays*24*3600;
            if($max_diff_hours < $diff){
                throw new Exception("No se puede consultar un rango mayor a {$maxDays} dias");
            }
            $minDateTime = new DateTime();
            $minDateTime->modify("-{$maxDays} day");
            if($minDateTime->format("Ymd") > $dtFechaIni->format("Ymd")){
                throw new Exception("No se puede consultar un rango mayor a {$maxDays} dias");
            }
            if($dtFechaFin->format("Y-m-d") > $now->format("Y-m-d")){
                throw new Exception("No se puede consultar una fecha superior al dia actual");
            }

            $data = $this->repository->getReport($base, $dtFechaIni, $dtFechaFin, $whiteList);

            $content = "";
            $content .= "MSISDN".PHP_EOL;
            foreach($data as $row){
                $content .= $row["msisdn"].PHP_EOL;
            }

            $baseName = $this->baseById[$base]["name"];

            $now = new DateTime();
            $strNow = $now->format("YmdHis");
            $whiteListSubName = (int) $whiteList === 1 ? "WHITE_LIST_" : "";
            $filename = "BASE_{$baseName}_{$whiteListSubName}{$strNow}.csv";

            $this->repository->saveLog($nintex, $base, $dtFechaIni, $dtFechaFin, $now, $filename, $whiteList);

            $this->storage->put(SaveReportLog::LOCAL_PATH."/GEOMARKETING/{$filename}", $content);
            $this->reportLog(SaveReportLog::LOCAL_PATH."/GEOMARKETING/{$filename}", $filename, $fechaIniExec, new DateTime(), $reporteInput, null);

            return new Response([], [
                "filename" => $filename,
                "type" => "csv",
                "content" => $content,
            ]);
        } catch (\Throwable $th) {
            $this->reportLog(null, null, $fechaIniExec, new DateTime(), $reporteInput, $th);
            throw $th;
        }
    }

    private function reportLog(?string $local_file, ?string $filename, DateTime $ini, DateTime $fin, array $input, ?\Throwable $error)
    {
        $logDto = SaveReportDto::create(
            'GEOMARKETING',
            $filename,
            $ini,
            $fin,
            $error === null ? ReportLogStatus::CORRECTO : ReportLogStatus::ERROR,
            $error !== null ? $error->getMessage() : null,
            'geomarketing',
            json_encode($input)
        );
        $this->saveReportLog->create($logDto);
        if ($local_file !== null) {
            $this->saveReportLog->sendFileToRemoteServer($local_file, "GEOMARKETING/{$filename}");
        }
    }
}
