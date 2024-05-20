<?php

namespace AMovil\Reports\GeoMarketing\Services;

use AMovil\Reports\GeoMarketing\Domain\GeoMarketingRepository;
use AMovil\Shared\Application\Response;
use DateInterval;
use DateTime;
use Exception;

class ExportGeoMarketing
{
    private $baseById = [
        "1" => ["name" => "ESTADIO_NACIONAL"],
        "2" => ["name" => "ESTADIO_MONUMENTAL"],
        "3" => ["name" => "ESTADIO_MONUMENTAL_DE_LA_UNSA"],
    ];
    private $repository;
    const MAX_DAYS = 30;

    public function __construct(GeoMarketingRepository $repo)
    {
        $this->repository = $repo;
    }

    public function __invoke($nintex, $base, $fechaIni, $fechaFin, $whiteList)
    {
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

        return new Response([], [
            "filename" => $filename,
            "type" => "csv",
            "content" => $content,
        ]);
    }
}
