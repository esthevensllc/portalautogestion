<?php

namespace AMovil\Reports\DAPU\EquipoBiometria\Services;

use AMovil\Reports\DAPU\EquipoBiometria\Domain\EquipoBiometriaRepository;
use AMovil\Shared\Application\Response;

class GetEquipoBiometria
{
    private $repo;

    public function __construct(EquipoBiometriaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($dni, $fono, $periodo)
    {    
        $data = $this->repo->getByDni_Fono_Periodo($dni, $fono, $periodo);
        return new Response([], $data);
    }
}
