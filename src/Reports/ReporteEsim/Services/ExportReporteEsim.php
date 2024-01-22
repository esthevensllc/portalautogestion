<?php

namespace AMovil\Reports\ReporteEsim\Services;

use AMovil\Reports\ReporteEsim\Domain\ReporteEsimRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use AMovil\Shared\FileStorage\Domain\StorageService;
use AMovil\Shared\FileStorage\Domain\StorageSystemName;
use DateTime;
use Exception;

class ExportReporteEsim
{
    private $repo;
    private $exportService;
    private $storage;
    private $baseStoragePath;
    
    public function __construct(ReporteEsimRepository $repo, ExportService $exportService, StorageService $storageService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->storage = $storageService->getStorageSystemByName(StorageSystemName::DN04);
        $this->baseStoragePath = "/space/reportes/reportes_esim/output";
    }

    public function __invoke(string $nintex, FileInput $file)
    {
        $this->guard($nintex ,$file);
        $data = $this->getDataFromCsv($file);
        $result = $this->repo->getReporte($nintex, $data);
        $writer = $this->export($result);
        $content = $writer->getOutput();

        $filename = "reporte_esim_{$nintex}.csv";
        $this->storage->put("{$this->baseStoragePath}/{$filename}", $content);
        return Response::respData([
            "filename" => $filename,
            "type" => "csv",
            "content" => $content
        ]);
    }

    private function guard($nintex, FileInput $file){
        if($file->getExtension() !== "csv"){
            throw new Exception("El archivo deve ser un csv");
        }
        if($this->repo->nintexIsProcessed($nintex)){
            throw new Exception("El nintex {$nintex} ya fue procesado");
        }
    }

    private function export($result)
    {
        $headers = [
            "fecha" => ["label" => "FECHA"],
            "msisdn" => ["label" => "N_LINEA"],
            "iccid" => ["label" => "ICCID"],
            "imsi_nuevo" => ["label" => "IMSI_NUEVO"],
            "tac" => ["label" => "TAC"],
            "descripcion_gsma" => ["label" => "DESCRIPCION_GSMA"],
        ];

        $options = ["rowType" => "array"];

        $this->exportService->loadData($headers, $result, $options);
        return $this->exportService->getWriter(WriterType::CSV);
    }

    private function getDataFromCsv(FileInput $file)
    {
        $values = [];
        // $reader = IOFactory::createReader('Csv');
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        $reader->setDelimiter(",");
        $reader->setInputEncoding("UTF-8");
        $spreedsheet = $reader->load($file->getFilePath());
        $sheet = $spreedsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $headers = ["FECHA","N_LINEA","ICCID","IMSI_NUEVO","TAC",'DESCRIPCI'];
        if($highestColumn !== "F"){
            throw new Exception("El archivo debe tener 6 cabeceras");
        }
        $counter = 1;
        for ($i=1; $i <= 6; $i++) {
            $header = $sheet->getCellByColumnAndRow($counter, 1)->getValue();
            // $matches = [];
            // preg_match('/'.$headers[$counter-1].'/', $header, $matches);
            if(!str_contains($header, $headers[$counter-1])){
                throw new Exception("La cabecera {$header} debe ser igual a {$headers[$counter-1]}");
            }
            $counter += 1;
        }

        for ($i=2; $i <= $highestRow; $i++) {
            $row = [
                "fecha" => $sheet->getCellByColumnAndRow(1, $i)->getValue(),
                "msisdn" => $sheet->getCellByColumnAndRow(2, $i)->getValue(),
                "iccid" => $sheet->getCellByColumnAndRow(3, $i)->getValue(),
                "imsi_nuevo" => $sheet->getCellByColumnAndRow(4, $i)->getValue(),
                "tac" => $sheet->getCellByColumnAndRow(5, $i)->getValue(),
                "descripcion_gsma" => $sheet->getCellByColumnAndRow(6, $i)->getValue(),
            ];
            $dtFecha = DateTime::createFromFormat("Y-m-d", $row["fecha"]);
            if($dtFecha === false){
                $dtFecha = DateTime::createFromFormat("d/m/Y", $row["fecha"]);
            }
            if($dtFecha === false){
                throw new Exception("La fecha {$row['fecha']} no es valida");
            }
            $row["fecha"] = $dtFecha->format("Y-m-d");
            $values[] = $row;
        }
        return $values;
    }
}
