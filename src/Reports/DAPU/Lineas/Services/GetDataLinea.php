<?php

namespace AMovil\Reports\DAPU\Lineas\Services;

use AMovil\Reports\DAPU\Lineas\Domain\LineaRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GetDataLinea
{
    private $repo;

    public function __construct(LineaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke(int $tipoInput, array $values): Response
    {
        $data = [];
        if(in_array($tipoInput, [1,3])){
            $data = $this->repo->getUsuariosByLinea($values);
        } else if (in_array($tipoInput, [2,4])){
            $data = $this->repo->getUsuariosByDni($values);
        }
        return new Response([], $data);
    }

    public function fromFile($tipoInput, FileInput $file){
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
        
        return $this->__invoke($tipoInput, $data);
    }
}
