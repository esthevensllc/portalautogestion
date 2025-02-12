<?php

namespace AMovil\Reports\ListaExcepcionesArt25\Services;

use AMovil\Reports\ListaExcepcionesArt25\Domain\ListaExcepcionesArt25Repository;
use AMovil\Shared\Application\FileInput;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ListaExcepcionesArt25Finder
{
    private $repo;

    public function __construct(ListaExcepcionesArt25Repository $repo)
    {
        $this->repo = $repo;
    }

    public function findLast()
    {
        return $this->repo->findLast();
    }

    public function findByImei($imeis)
    {
        return $this->repo->getByImei($imeis);
    }

    public function fromFile(FileInput $file){
        if(!in_array($file->getExtension(), ["csv"])){
            throw new Exception("La extension {$file->getExtension()} no es valida");
        }
        $extension = ucfirst($file->getExtension());
        $reader = IOFactory::createReader($extension);
        $spreedsheet = $reader->load($file->getFilePath());
        $sheet = $spreedsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();

        $data = [];
        for ($i=1; $i <= $highestRow; $i++) {
            $data[] = trim($sheet->getCellByColumnAndRow(1, $i)->getValue());
        }
        
        return $this->repo->getByImei($data);
    }
}
