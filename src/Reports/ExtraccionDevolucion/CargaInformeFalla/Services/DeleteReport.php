<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Services;

use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;
use Illuminate\Support\Facades\Storage;

class DeleteReport
{
    private $repo;
    
    public function __construct(ExtraccionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($id)
    {
        $result = $this->repo->findInputFor($id);

        Storage::delete('carga_informe_falla/'. $id.'_'.$result[0]->name_file);

        $data = $this->repo->deleteRecord($id);

        return [
            'data' => $data
        ];
    }
}
