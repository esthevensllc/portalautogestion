<?php

namespace AMovil\Reports\RepFiscalia\Services;

use AMovil\Reports\RepFiscalia\Domain\ReporteFiscalRepository;
use DateTime;

class ReporteFiscalValidator
{
    private $repo;

    public function __construct(ReporteFiscalRepository $repo)
    {
        $this->repo = $repo;    
    }

    public function __invoke($msisdn, $fecha1, $fecha2)
    {
        $dt1 = DateTime::createFromFormat('Y-m-d', $fecha1);
        $dt2 = DateTime::createFromFormat('Y-m-d', $fecha2);

        $errors = [];
        if($dt1 && $dt2){
            if(!$this->repo->msisdnExists($msisdn, $dt1, $dt2)){
                $errors['message'] = 'No existe el msisdn ingresado';
            }else if(!$this->repo->hasRecords($msisdn, $dt1, $dt2)){
                $errors['message'] = 'No se contiene esa información de ese msisdn para el periodo ingresado';
            }
        }else{
            $errors['message'] = 'Las fechas no son validas';
        }
        return ['passes' => count($errors) === 0, 'errors' => $errors];
    }
}
