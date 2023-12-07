<?php

namespace AMovil\Reports\General\BloqueoImei\Services;

use AMovil\Reports\General\BloqueoImei\Domain\BloqueoImeiRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TripletaFinder
{
    private $repo;

    public function __construct(BloqueoImeiRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getBy($field, $value)
    {
        return $this->repo->getTripletaByCriteria([[$field, $value]]);
    }

    public function getByFile(string $field, FileInput $value): Response
    {
        $errors = [];
        if($value->getExtension() !== "xlsx"){
            $errors["message"] = "El formato del archivo deve ser un xlsx";
        }
        if(count($errors) === 0){
            $values = $this->getDataFromFile($value);
            $data = $this->repo->getTripletaByCriteria([[$field, $values]]);
            return Response::respData($data);
        }
        return new Response($errors);
    }

    private function getDataFromFile(FileInput $file)
    {
        $values = [];
        if ($file !== null) {
            $reader = IOFactory::createReader('Xlsx');
            $spreedsheet = $reader->load($file->getFilePath());
            $sheet = $spreedsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            for ($i=1; $i <= $highestRow; $i++) {
                $values[] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
            }
        }
        return $values;
    }
}
