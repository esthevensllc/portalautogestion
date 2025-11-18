<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\ExtraccionRepository;

class ValidacionRecargaUpdater
{
    private $extraccionRepo;

    public function __construct(
        ExtraccionRepository $extraccionRepo
    ) {
        $this->extraccionRepo = $extraccionRepo;
    }
    
    public function __invoke($ticket)
    {
        $this->extraccionRepo->saveReporteValidacionRecarga($ticket);
    }
}
