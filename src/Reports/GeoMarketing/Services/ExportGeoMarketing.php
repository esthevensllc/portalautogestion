<?php

namespace AMovil\Reports\GeoMarketing\Services;

use AMovil\Reports\GeoMarketing\Domain\GeoMarketingRepository;
use AMovil\Shared\Application\Response;
use DateInterval;
use DateTime;
use Exception;

class ExportGeoMarketing
{
    private $repository;

    public function __construct(GeoMarketingRepository $repo)
    {
        $this->repository = $repo;
    }

    public function __invoke($nintex, $base, $fechaIni, $fechaFin)
    {
        $dtFechaIni = DateTime::createFromFormat("Y-m-d H:i", $fechaIni);
        $dtFechaFin = DateTime::createFromFormat("Y-m-d H:i", $fechaFin);
        $now = new DateTime();
        $diff = $dtFechaFin->getTimestamp() - $dtFechaIni->getTimestamp();
        $max_diff_hours = 3*3600;
        if($max_diff_hours < $diff){
            throw new Exception("No se puede consultar un rango mayor a 2 horas");
        }
        if($dtFechaIni->format("Y-m-d") !== $now->format("Y-m-d") || $dtFechaFin->format("Y-m-d") !== $now->format("Y-m-d")){
            throw new Exception("No se puede consultar un dia diferente al dia actual");
        }

        $data = $this->repository->getReport($dtFechaIni, $dtFechaFin);

        $content = "";
        $content .= "MSISDN".PHP_EOL;
        foreach($data as $row){
            $content .= $row["msisdn"].PHP_EOL;
        }

        $now = new DateTime();
        $strNow = $now->format("YmdHis");
        $filename = "BASE_ESTADIO_NACIONAL_{$strNow}.csv";

        $this->repository->saveLog($nintex, $base, $dtFechaIni, $dtFechaFin, $now, $filename);

        return new Response([], [
            "filename" => $filename,
            "type" => "csv",
            "content" => $content,
        ]);

        /*
        $tempFilename = "{$this->temp_storage_path}/".Uuid::uuid4()->toString().".csv";
        $file = fopen($tempFilename, "w");
        fwrite($file, "SUBSCRIPTION_ACCESS_NUMBER".PHP_EOL);
        foreach($data as $row){
            fwrite($file, $row->msisdn.PHP_EOL);
        }
        fclose($file);
        */

    }
}
