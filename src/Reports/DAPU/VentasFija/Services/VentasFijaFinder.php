<?php

namespace AMovil\Reports\DAPU\VentasFija\Services;

use AMovil\Reports\DAPU\VentasFija\Domain\VentasFijaRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VentasFijaFinder
{
    private $repo;

    public function __construct(VentasFijaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke(array $values)
    {
        $data = $this->repo->getByNumDocumento($values);
        return new Response([], $data);
    }

    public function fromFile(FileInput $file){
        if(!in_array($file->getExtension(), ["csv"])){
            return new Response(["message" => "La extension {$file->getExtension()} no es valida"]);
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
        
        return $this->__invoke($data);
    }
}
