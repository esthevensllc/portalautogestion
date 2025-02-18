<?php

namespace AMovil\Reports\DAPU\UltimoTrafico\Services;

use AMovil\Reports\DAPU\UltimoTrafico\Domain\UltimoTraficoRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UltimoTraficoFinder
{
    private $repo;

    public function __construct(UltimoTraficoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke(int $tipoInput, array $values)
    {
        if(in_array($tipoInput, [1,3])){
            $data = $this->repo->getByMsisdn($values);
        } else if (in_array($tipoInput, [2,4])){
            $data = $this->repo->getByImeis($values);
        }
        return new Response([], $data);
    }

    public function fromFile(int $tipoInput, FileInput $file){
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
        
        return $this->__invoke($tipoInput, $data);
    }
}
