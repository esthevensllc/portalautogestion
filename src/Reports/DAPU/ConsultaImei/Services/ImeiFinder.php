<?php

namespace AMovil\Reports\DAPU\ConsultaImei\Services;

use AMovil\Reports\DAPU\ConsultaImei\Domain\ConsultaImeiRepository;
use AMovil\Shared\Application\Response;
use DateTime;

class ImeiFinder
{
    private $repo;

    public function __construct(ConsultaImeiRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($msisdn, $fecha)
    {
        $dtFecha = DateTime::createFromFormat("Y-m-d", $fecha);
        $data = $this->repo->getByMsisdnAndFecha($msisdn, $dtFecha);
        return Response::respData($data);
    }
}
