<?php

namespace AMovil\Reports\DAPU\TramiteConsulta\Services;

use AMovil\Reports\DAPU\TramiteConsulta\Domain\TramiteConsultaRepository;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExportReporteTramiteConsulta
{
    private $repo;
    private $exportService;
    private $saveReportLog;

    public function __construct(TramiteConsultaRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
    }

    public function __invoke($tipo_formato, $file): Response
    {
        $dt_start = new DateTime();
        try {
            $input_data = $this->getDataFromCsv($tipo_formato, $file);
        
            //dd(gettype($csv[0]));
            //dd();
            
            $data = [];
            $filename = "";
            switch ($tipo_formato) {
                case '1':
                    $data = $this->repo->getReporteF1($input_data['values']);

                    $headers = [
                        "y" => ["label" => "ANIO"],
                        "mes" => ["label" => "MES"],
                        //"linea" => ["label" => "LINEA"],
                        "departamento" => ["label" => "DEPARTAMENTO"],
                        "servicioinvolucrado" => ["label" => "SERVICIOINVOLUCRADO"],
                        "canal_atencion" => ["label" => "CANAL_ATENCION"],
                        "tipo_tramite" => ["label" => "TIPO_TRAMITE"],
                        "total_tramites" => ["label" => "TOTAL_TRAMITES"],
                    ];
                    $filename = "REPORTE_F1.csv";

                    break;
                case '2':
                    $data = $this->repo->getReporteF2($input_data['values']);

                    $headers = [
                        "y" => ["label" => "ANIO"],
                        "mes" => ["label" => "MES"],
                        //"linea" => ["label" => "LINEA"],
                        "departamento" => ["label" => "DEPARTAMENTO"],
                        "servicioinvolucrado" => ["label" => "SERVICIOINVOLUCRADO"],
                        "canal_atencion" => ["label" => "CANAL_ATENCION"],
                        "tipo_tramite" => ["label" => "TIPO_TRAMITE"],
                        "estado_tramite" => ["label" => "ESTADO_TRAMITE"],
                        "total_tramites" => ["label" => "TOTAL_TRAMITES"],
                    ];
                    $filename = "REPORTE_F2.csv";

                    break;
                default:
                    break;
            }

            $i = 0;
            foreach($headers as $index => $row){
                if($index !== 'departamento'){
                    $headers[$index]['label'] = utf8_encode($input_data['headers'][$i]);
                }
                $i++;
            }
            //dd($data);
            $this->exportService->loadData($headers, $data);
            $temp_filename = storage_path("app/public/TRAMITE_CONSULTA_".$dt_start->format("YmdHis").".csv");
            $this->exportService->getWriter(WriterType::CSV)->save($temp_filename);
            $content = file_get_contents($temp_filename);

            $this->reportLog($temp_filename, $dt_start, new DateTime(), ['filename' => "TRAMITE_CONSULTA_".$dt_start->format("YmdHis").".csv"]);
            unlink($temp_filename);

            return new Response([], [
                "filename" => $filename,
                "content" => $content
            ]);
        } catch (\Throwable $th) {
            $this->reportLog($temp_filename, $dt_start, new DateTime(), ['mensaje' => $th->getMessage()]);
            throw $th;
        }
    }

    public function getDataFromCsv($tipo_format, $file): array {
        $n_headers = $tipo_format === '1' ? 7 : 8;
        $headers = [];
        $values = [];
        if ($file !== null) {
            /*$reader = IOFactory::createReader(WriterType::CSV);
            $spreedsheet = $reader->load($file['pathname']);
            $sheet = $spreedsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();

            
            for ($j=1; $j <= $n_headers; $j++) {
                $headers[] = mb_convert_encoding($sheet->getCellByColumnAndRow($j, 1)->getValue(), 'utf-8');
            }

            for ($i=2; $i <= $highestRow; $i++) {
                $row = [];
                for ($j=1; $j <= $n_headers; $j++) {
                    $row[] = $sheet->getCellByColumnAndRow($j, $i)->getValue();
                }
                $values[] = $row;
            }*/
            $csv = file_get_contents($file['pathname']);
            $csv_lines = explode("\r\n", $csv);
            $csv_array = [];
            foreach ($csv_lines as $value) {
                # code...
                // $csv_row = str_getcsv($csv, ",", '"', "\n");
                if(trim($value) !== ""){
                    $csv_row = str_getcsv($value);
                    $csv_array[] = $csv_row;
                }
            }
            $headers = $csv_array[0];
            for ($i=1; $i < count($csv_array); $i++) { 
                $values[] = $csv_array[$i];
            }
        }
        return ['headers' => $headers, "values" => $values];
    }

    private function reportLog($local_file, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        $data = array_merge([
            'name' => 'DAPU_TRAMITE_CONSULTA',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => "dapu.tramites-consulta",
        ], $extra_data);
        $this->saveReportLog->__invoke($data, $local_file, 'DAPU/TRAMITE_CONSULTA');
    }
}
