<?php

namespace AMovil\Reports\DAPU\LogBiometria\Services;

use AMovil\Reports\DAPU\LogBiometria\Domain\LogBiometriaRepository;
use AMovil\Shared\Application\Response;

class GetLogBiometria
{
    private $repo;

    public function __construct(LogBiometriaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($dni, $periodo)
    {
        // 1️⃣ Limpiar espacios
        $dtPeriodo = trim($periodo);

        // 3️⃣ Llamar al repositorio
        $data = $this->repo->getByDniAndPeriodo($dni, $dtPeriodo);
        return new Response([], $data);
    }
}
