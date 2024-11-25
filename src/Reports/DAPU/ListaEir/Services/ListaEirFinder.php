<?php

namespace AMovil\Reports\DAPU\ListaEir\Services;

use AMovil\Reports\DAPU\ListaEir\Domain\ListaEirRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ListaEirFinder
{
    private $repo;

    public function __construct(ListaEirRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke(array $imeis)
    {
        $validationResp = $this->validation($imeis);
        if($validationResp !== null){
            return $validationResp;
        }
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

    private function validation(array $imeis){
        foreach($imeis as $imei){
            if(strlen($imei) !== 14){
                return new Response(["message" => "El imei {$imei} debe tener 14 digitos"]);
            }
        }
        return null;
    }
}
