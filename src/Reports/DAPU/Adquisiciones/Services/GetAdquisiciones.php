<?php

namespace AMovil\Reports\DAPU\Adquisiciones\Services;

use AMovil\Reports\DAPU\Adquisiciones\Domain\AdquisicionRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GetAdquisiciones
{
    private $repo;

    public function __construct(AdquisicionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke(array $imeis): Response
    {
        $data = $this->repo->getByImeis($imeis);
        return new Response([], $data);
    }

    public function fromFile(FileInput $file){
        if(!in_array($file->getExtension(), ["csv", "xlsx"])){
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
        
        return $this->__invoke($data);
    }
}
