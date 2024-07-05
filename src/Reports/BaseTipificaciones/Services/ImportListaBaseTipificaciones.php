<?php

namespace AMovil\Reports\BaseTipificaciones\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\BaseTipificaciones\Domain\BaseTipificacionesRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Ramsey\Uuid\Uuid;

class ImportListaBaseTipificaciones
{
    private $repo;
    private $authService;

    public function __construct(
        BaseTipificacionesRepository $repo,
        AuthService $authService
    ) {
        $this->repo = $repo;
        $this->authService = $authService;
    }

    public function __invoke(FileInput $documento): Response
    {
        $originalFilename = $documento->getFilename();

        $strNow = (new DateTime())->format("YmdHis");
        
        $response = $this->validateAndGetFileData($documento);
        if($response->fails()){
            return $response;
        }

        $result = $this->repo->procesarData($response->data());

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Agregar encabezados
        $sheet->fromArray([
            'LINEA', 'TIPO_DOC', 'NRO_DOC', 'NOMBRE', 'CUSTOMER_TYPE', 'AGREEMENT_MODE', 'AGREEMENT_PRODUCT_OFFERING_DESC', 'STATUS',
            'SUBSCRIPTION_SOURCE_SYSTEM_DESC', 'PLATAFOMA', 'SUBSCRIPTION_START_DATE', 'SUBSCRIPTION_END_DATE', 'ID_CLIENTE', 'CUENTA',
            'IMEI', 'IMSI'
        ], NULL, 'A1');

        // Agregar datos
        $rowNumber = 2;
        foreach ($result as $row) {
            $sheet->fromArray((array)$row, NULL, 'A' . $rowNumber++);
        }

        // Guardar el archivo Excel
        $writer = new Xlsx($spreadsheet);
        $fileName = 'resultado_'.$strNow.'.xlsx';
        $filePath = storage_path($fileName);
        $writer->save($filePath);

        // Validar los datos no ubicados
        $notFound = $this->repo->validarData();

        return new Response([], [
            'file' => $fileName,
            'notFound' => $notFound
        ]);
    }

    private function validateAndGetFileData(FileInput $file): Response {
        if(!in_array($file->getExtension(), ["xlsx", "xls", "txt", "csv"])){
            return new Response(["message" => "El archivo no es valido solo se permiten (xlsx, xls, txt y csv)"]);
        }
        $readerType = ucfirst($file->getExtension());
        if($readerType === "Txt"){
            $readerType = "Csv";
        }
        $values = [];
        $reader = IOFactory::createReader($readerType);
        $spreedsheet = $reader->load($file->getFilePath());
        $sheet = $spreedsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        if($sheet->getHighestColumn() !== "C"){
            return new Response(["message" => "El archivo tiene que tener exactamente tres columnas"]);
        }
        for ($i=1; $i <= $highestRow; $i++) {
            $row = [
                "imei" => trim($sheet->getCell("A{$i}")->getValue()),
                "imsi" => trim($sheet->getCell("B{$i}")->getValue()),
                "linea" => trim($sheet->getCell("C{$i}")->getValue()),
            ];
            $errors = [];
            $imei = $row["imei"];
            if(!is_numeric($imei)){
                $errors[] = "El imei '{$imei}' no es valido";
            }
            $imsi = $row["imsi"];
            if(!is_numeric($imsi)){
                $errors[] = "El imsi '{$imsi}' no es valido";
            }
            $linea = $row["linea"];
            if(!is_numeric($linea)){
                $errors[] = "La linea '{$linea}' no es valida";
            }
            if(count($errors) > 0){
                return new Response(["message" => "Fila {$i}: ".implode(", ", $errors)]);
            }
            $values[] = $row;
        }
        return new Response([], $values);
    }
}
