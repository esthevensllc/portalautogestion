<?php

namespace AMovil\Reports\IMRIMF\Controllers;

use AMovil\Reports\IMRIMF\Services\ImrFinder;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ImrController
{
    private ImrFinder $finder;

    public function __construct(ImrFinder $finder)
    {
        $this->finder = $finder;
    }

    public function view()
    {
        $config = [
            'title' => 'Consulta de Acciones IMR',
            'title_percent' => 'Porcentaje de uso de IMR',
            'product_label' => 'IMR',
            'input_label' => 'Customer ID',
            'input_placeholder' => 'Ej: H16805491',
            'search_url' => rtrim(url()->current(), '/') . '/search',
            'initial_chart' => [
                'hasData' => false,
                'total' => 0,
                'saldo' => 0,
                'cantidadAcciones' => 0,
                'montoConsumido' => 0,
            ],
        ];

        return view('imrimf.imr', compact('config'));
    }

    public function search(Request $request)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->finder->search((string) $request->query('telefono', '')),
            ]);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo consultar la información IMR. Revise permisos, conexión o existencia de la tabla CLIATC.CI_PBI_ACCIONES_IMR.',
            ], 500);
        }
    }
}
