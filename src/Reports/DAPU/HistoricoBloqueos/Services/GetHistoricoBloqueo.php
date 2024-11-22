<?php

namespace AMovil\Reports\DAPU\HistoricoBloqueos\Services;

use AMovil\Reports\DAPU\HistoricoBloqueos\Domain\HistoricoBloqueoRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GetHistoricoBloqueo
{
    private $repo;

    public function __construct(HistoricoBloqueoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($imeis)
    {    
        $data = $this->repo->getByImeis($imeis);
        return new Response([], $data);
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
        
        return $this->__invoke($data);
    }
}
