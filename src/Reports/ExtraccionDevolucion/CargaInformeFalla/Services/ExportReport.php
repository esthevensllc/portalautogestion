<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;

class ExportReport
{
    private $repo;
    
    public function __construct(ExtraccionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($numero)
    {
        $data = $this->repo->findInputFor($numero);
        $file = storage_path('app/carga_informe_falla/'.$numero.'_'.$data[0]->name_file);
        return response()->download($file);
    }
}
