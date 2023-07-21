<?php

namespace AMovil\Reports\RepDetConsumo\Services;

use AMovil\Reports\RepDetConsumo\Infrastructure\Repository\LaravelDetalleConsumoRepository;
use AMovil\Shared\Application\Response;

class ClienteValidator
{
    private $repo;

    public function __construct(LaravelDetalleConsumoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke($cliente): Response
    {
        $cliente = $this->repo->findCliente($cliente);
        $data = [];
        if ($cliente !== null){
            //$data = ['min_periodo' => $min_periodo];
            $data = $cliente;
        }
        return new Response([], $data);
    }
}
