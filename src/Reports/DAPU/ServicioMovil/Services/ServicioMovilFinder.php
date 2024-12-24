<?php

namespace AMovil\Reports\DAPU\ServicioMovil\Services;

use AMovil\Reports\DAPU\ServicioMovil\Domain\ServicioMovilRepository;
use AMovil\Shared\Application\Response;
use DateTime;
use Exception;

class ServicioMovilFinder
{
    private $repo;

    public function __construct(ServicioMovilRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($msisdn, $fecha)
    {
        $dtFecha = DateTime::createFromFormat("Y-m-d", $fecha);
        if($dtFecha === false){
            throw new Exception("La fecha no es valida");
        }
        $data = $this->repo->getByMsisdnFecha($msisdn, $dtFecha);
        return new Response([], $data);
    }
}
