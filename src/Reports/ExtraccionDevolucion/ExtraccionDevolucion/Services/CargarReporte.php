<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\ExtraccionRepository;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository as InformeFallasRepository;
use AMovil\Reports\ExtraccionDevolucion\TicketReports\Domain\TicketReportRepository;
use AMovil\Shared\EmailNotification\Domain\EmailNotification;
use AMovil\Shared\EmailNotification\Domain\EmailNotificationService;
use AMovil\Shared\NotificationUser\Domain\NotificationUserRepository;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CargarReporte
{
    private $repo;
    private $informeRepo;
    private $ticketRepo;
    private $notificationUserRepo;
    private $emailNotification;
    private $groupId;
    
    public function __construct(
        ExtraccionRepository $repo,
        InformeFallasRepository $informeRepo,
        TicketReportRepository $ticketRepo,
        NotificationUserRepository $notificationUserRepo,
        EmailNotificationService $emailNotification,
    ){
        $this->repo = $repo;
        $this->informeRepo = $informeRepo;
        $this->ticketRepo = $ticketRepo;
        $this->notificationUserRepo = $notificationUserRepo;
        $this->emailNotification = $emailNotification;
        $this->groupId = config("app.env")."/ext_acreditado";
    }

    public function __invoke($ticket, $departamento, $excel, $tipo)
    {
        $data = $this->getDataFromExcel($excel);
        $this->repo->updateReporte($ticket, $departamento, $data);
        // $data = $this->getDataFromLog($excel);
        // $this->repo->saveAcreditacionPrepago($ticket, $departamento, $data);

        $informes = $this->informeRepo->getReportesByCriteria([["ticket", $ticket]]);
        if (count($informes)>0) {
            $informe = $informes[0];
            $registroTicket = $this->ticketRepo->findByTicket($ticket);
            $porcentaje = round($registroTicket->acreditados_post / $registroTicket->numero_afectados_post, 2);
            if ($porcentaje > 0.5) {
                $this->informeRepo->acreditadoPost($informe->numero_de_reporte, true);

                $emails = $this->notificationUserRepo->getEmailsByGroupId($this->groupId);
                $email = new EmailNotification();
                $email->to($emails)
                ->subject("Extracción y Devolución / Notificación de ticket acreditado Postpago")
                ->view("mails.extraccionAcreditado")
                ->with(["informeFallas" => $registroTicket, "modalidad" => "Postpago"]);
                $this->emailNotification->send($email);
            } else {
                $this->informeRepo->acreditadoPost($ticket, false);
            }
        } else {
            throw new Exception("No existe un informe de fallas con el ticket ingresado");
        }
    }

    private function getDataFromExcel($excel)
    {
        $values = [];
        if ($excel !== null) {
            $reader = IOFactory::createReader('Xlsx');
            $spreedsheet = $reader->load($excel->getPathname());
            $this->validateExcel($spreedsheet);
            $sheet = $spreedsheet->getSheet(1);
            $highestRow = $sheet->getHighestRow();
            for ($i=2; $i <= $highestRow; $i++) {
                $row = [];
                $row["ticket"] = $sheet->getCellByColumnAndRow(1, $i)->getValue();
                $row["msisdn"] = $sheet->getCellByColumnAndRow(2, $i)->getValue();
                // $row["mto_dev_facturacion"] = $sheet->getCellByColumnAndRow(19, $i)->getOldCalculatedValue();
                $row["mto_dev_facturacion"] = $sheet->getCellByColumnAndRow(19, $i)->getValue();
                //if(str_starts_with($row["mto_dev_facturacion"], "=")){
                    $row["mto_dev_facturacion"] = $sheet->getCellByColumnAndRow(19, $i)->getOldCalculatedValue();
                //}
                $row["mto_dev"] = $sheet->getCellByColumnAndRow(20, $i)->getValue();
                //if(str_starts_with($row["mto_dev"], "=")){
                    $row["mto_dev"] = $sheet->getCellByColumnAndRow(20, $i)->getOldCalculatedValue();
                //}
                $row["factura_aplicada"] = $sheet->getCellByColumnAndRow(21, $i)->getValue();
                $row["fecha_devolucion"] = $this->formatExcelDate($sheet->getCellByColumnAndRow(22, $i)->getValue());
                $row["fecha_registro_devolucion"] = $this->formatExcelDate($sheet->getCellByColumnAndRow(23, $i)->getValue());
                $row["observacion"] = $sheet->getCellByColumnAndRow(24, $i)->getValue();
                $row["fecha_baja_facturacion"] = $this->formatExcelDate($sheet->getCellByColumnAndRow(25, $i)->getValue());
                $values[] = $row;
            }
        }
        return $values;
    }

    private function validateExcel(Spreadsheet $spreedsheet)
    {
        $sheetCount = $spreedsheet->getSheetCount();
        if($sheetCount !== 2){
            throw new Exception("El número de hojas deben ser dos");
        }
        $sheet = $spreedsheet->getSheet(1);
        $firstRowCount = $sheet->getHighestColumn(1);
        if($firstRowCount !== "Y"){
            throw new Exception("El número de columnas deben ser 25(columna 'Y' como máximo)");
        }
    }

    private function formatExcelDate($strDate)
    {
        $output = null;
        if(is_numeric($strDate)){
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($strDate);
            $output = $date->format("Y-m-d")." 00:00:00";
        }else{
            $date = DateTime::createFromFormat('d/m/Y', $strDate);
            if($date){
                $output = $date->format("Y-m-d")." 00:00:00";
            }
        }
        return $output;
    }

    private function getDataFromLog($file)
    {
        $values = [];
        if ($file !== null) {
            // $file_contents = file_get_contents($file->getPathname());
            $file = fopen($file->getPathname(), "r");
            $row = [];
            $count = 1;
            while(!feof($file)){
                $line = fgets($file);
                if(str_contains($line, "#@TRANSACTION")){
                    $row = [];
                }else if(str_contains($line, "#@END_TRANSACTION")){
                    $values[] = $row;
                }else{
                    $line_values = [];
                    $output = str_replace([
                        "#CA::Modify:PackageItem(",
                        "CA::Modify:PackageItem(",
                        ");",
                        "=\"",
                        "\",",
                        ",",
                        "\n",
                        "\""
                    ], ["","","","=",",","&", "", ""], $line);
                    $output = str_replace(["#CA::Modify:CustomerLifeCycleState(", "CA::Modify:CustomerLifeCycleState("], ["",""], $output);
                    parse_str($output, $line_values);
                    $row = array_merge($row, $line_values);
                }
                // $count++;
                // if($count > 20){
                //     break;
                // }
            }
            fclose($file);
        }
        return $values;
    }
}
