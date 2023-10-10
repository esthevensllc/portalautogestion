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
    ];
    private $repository;

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
        $max_diff_hours = 3*24*3600;
        if($max_diff_hours < $diff){
            throw new Exception("No se puede consultar un rango mayor a 3 dias");
        }
        if($dtFechaIni->format("Y-m-d") !== $now->format("Y-m-d") || $dtFechaFin->format("Y-m-d") !== $now->format("Y-m-d")){
            // throw new Exception("No se puede consultar un dia diferente al dia actual");
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
