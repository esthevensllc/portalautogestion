<?php

namespace AMovil\Reports\RepDetConsumo\Services;

use AMovil\Reports\RepDetConsumo\Infrastructure\Repository\LaravelDetalleConsumoRepository;

class RecordsValidator
{
    private $repo;

    public function __construct(LaravelDetalleConsumoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($cliente, $periodo)
    {
        $errors = [];
        if($periodo === null){
            $errors['message'] = "El periodo es requerido";
        } else if(!$this->repo->clienteExists($cliente)){
            $errors['message'] = "El número de cuenta {$cliente} no existe";
        }else{
            $periodos = explode(",",trim($periodo));
            $invalid_periods = [];
            foreach($periodos as $p){
                if(!$this->repo->hasRecords($cliente, $p)){
                    $invalid_periods[] = $p;
                }
            }
            if(count($invalid_periods) > 0){
                $str_periodos = implode(",", $invalid_periods);
                $errors['message'] = "No hay registros para el cliente {$cliente} en los periodos {$str_periodos}. Por favor comunicarse con el área de Analitica y experiencia de servicio - Red";;
            }
        }
        return ['errors' => $errors, 'passes' => count($errors) === 0];
    }
}
