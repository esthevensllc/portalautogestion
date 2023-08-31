<?php

namespace AMovil\Reports\GeoMarketing\Services;

use AMovil\Reports\GeoMarketing\Domain\GeoMarketingRepository;
use AMovil\Shared\Application\Response;
use DateTime;

class ExportGeoMarketing
{
    private $repository;

    public function __construct(GeoMarketingRepository $repo)
    {
        $this->repository = $repo;
    }

    public function __invoke($fechaIni, $fechaFin)
    {
        $dtFechaIni = DateTime::createFromFormat("Y-m-d H:i", $fechaIni);
        $dtFechaFin = DateTime::createFromFormat("Y-m-d H:i", $fechaFin);

        $data = $this->repository->getReport($dtFechaIni, $dtFechaFin);

        $content = "";
        $content .= "MSISDN".PHP_EOL;
        foreach($data as $row){
            $content .= $row["msisdn"].PHP_EOL;
        }

        $strNow = (new DateTime())->format("YmdHis");

        return new Response([], [
            "filename" => "BASE_ESTADIO_NACIONAL_{$strNow}.csv",
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
