<?php

namespace AMovil\Reports\ExtraccionDevFija\InformesFalla\Services;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFallasRepository;

class DeleteInformeFallas
{
    private $repo;
    private $extraccionFijaRepo;

    public function __construct(
        InformeFallasRepository $repo,
        ExtraccionDevFijaRepository $extraccionFijaRepo
    ) {
        $this->repo = $repo;
        $this->extraccionFijaRepo = $extraccionFijaRepo;
    }

    public function __invoke(string $numReporte, $servicioAfectadoId)
    {
        $this->repo->delete($numReporte, $servicioAfectadoId);
        $this->extraccionFijaRepo->deleteServicioInput($numReporte, $servicioAfectadoId);
    }
}
