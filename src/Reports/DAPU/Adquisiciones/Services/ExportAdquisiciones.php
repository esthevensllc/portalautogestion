<?php

namespace AMovil\Reports\DAPU\Adquisiciones\Services;

use AMovil\Reports\DAPU\Adquisiciones\Domain\AdquisicionRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExportAdquisiciones
{
    private $repo;
    private $exportService;

    public function __construct(AdquisicionRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke(array $imeis, $type)
    {
        WriterType::guard($type);

        $headers = [
            "fecha_adquisicion" => ["label" => "Fecha adquisición"],
            "imei" => ["label" => "IMEI"],
            "msisdn" => ["label" => "Número"],
            "tipo_doc" => ["label" => "Tipo documento"],
            "num_doc" => ["label" => "Num doc"],
            "cliente" => ["label" => "Cliente"],
            "segmento" => ["label" => "Segmento"],
            "descrip_razon_venta" => ["label" => "Descripción razón venta"],
            "plan_adquirido" => ["label" => "Plan adquirido"],
            "marca" => ["label" => "Marca"],
            // "modelo" => ["label" => "Modelo"],
        ];
        $options = [
            'styles' => [
                'header' => ['font' => ['bold' => true]]
            ]
        ];
        $data = $this->repo->getByImeis($imeis);
        $this->exportService->loadData($headers, $data, $options);
        $content = $this->exportService->getWriter($type)->getOutput();
        $dt = new DateTime();
        
        return new Response([], [
            'filename' => "ADQUISICIONES_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }

    public function fromFile(FileInput $file, $type){
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
        
        return $this->__invoke($data, $type);
    }
}
