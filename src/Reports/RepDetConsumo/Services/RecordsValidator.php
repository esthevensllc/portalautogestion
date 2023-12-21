<?php

namespace AMovil\Reports\RepDetConsumo\Services;

use AMovil\Reports\RepDetConsumo\Infrastructure\Repository\LaravelDetalleConsumoRepository;
use DateTime;

class RecordsValidator
{
    private $repo;

    public function __construct(LaravelDetalleConsumoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($cliente, $periodo, $tipo_input, $fecha1, $fecha2)
    {
        $errors = [];
        if(($periodo === null && $tipo_input === '1') || (($fecha1 === null || $fecha2 === null) && $tipo_input === '2')){
            $errors['message'] = "El periodo es requerido";
        } else if(!$this->repo->clienteExists($cliente)){
            $errors['message'] = "El número de cuenta {$cliente} no existe";
        }else{
            $periodos = [];
            if($tipo_input === '1'){
                $periodos = explode(",",trim($periodo));
            }else{
                $periodos = $this->repo->getPeriodosByFechas(
                    $cliente,
                    DateTime::createFromFormat("Y-m-d", $fecha1),
                    DateTime::createFromFormat("Y-m-d", $fecha2)
                );
            }
            $invalid_periods = [];
            foreach($periodos as $p){
                if(!$this->repo->hasRecords($cliente, $p)){
                    $invalid_periods[] = $p;
                }
            }
            if(count($invalid_periods) > 0){
                $str_periodos = implode(",", $invalid_periods);
                $errors['message'] = "No hay registros para el cliente {$cliente} en los periodos {$str_periodos} en la tabla TEMP_TAG_11 de la DBTODB. Por favor comunicarse con el área de Factory Soporte Facturación - factsopfacturacion@claro.com.pe - COLOCAR EN EL ASUSTO LA PALABRA CLAVE \"TEMP_TAG_11 \"";
            }else{
                $invalid_periods = [];
                foreach($periodos as $p){
                    if(!$this->repo->invoicenumberHasRecords($cliente, $p)){
                        $invalid_periods[] = $p;
                    }
                }
                if(count($invalid_periods) > 0){
                    $str_periodos = implode(",", $invalid_periods);
                    $errors['message'] = "La factura para el numero de cuenta {$cliente} en los periodos {$str_periodos} no cuenta con registros en las siguientes tablas TEMP_TAG_1480, TEMP_TAG_1460 y TEMP_TAG_1470. Por favor comunicarse con el área de Factory Soporte Facturación - factsopfacturacion@claro.com.pe - COLOCAR EN EL ASUSTO LA PALABRA CLAVE \"TEMP_TAG_1480,TEMP_TAG_1460,TEMP_TAG_1470\"";
                }
            }
        }
        return ['errors' => $errors, 'passes' => count($errors) === 0];
    }
}
