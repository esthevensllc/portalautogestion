<?php

namespace AMovil\Reports\IMRIMF\Controllers;

use AMovil\Reports\IMRIMF\Services\ImfFinder;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ImfFijaController
{
    private ImfFinder $finder;

    public function __construct(ImfFinder $finder)
    {
        $this->finder = $finder;
    }

    public function view()
    {
        $config = [
            'title' => 'Consulta de Acciones IMF FIJA',
            'title_percent' => 'Porcentaje de uso de IMF',
            'product_label' => 'IMF',
            'input_label' => 'Teléfono',
            'input_placeholder' => 'Ej: 902857695',
            'search_url' => rtrim(url()->current(), '/') . '/search',
            'initial_chart' => [
                'hasData' => false,
                'total' => 0,
                'saldo' => 0,
                'cantidadAcciones' => 0,
                'montoConsumido' => 0,
            ],
        ];

        return view('imrimf.imf_fija', compact('config'));
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
                'message' => 'No se pudo consultar la información IMF FIJA. Revise permisos, conexión o existencia de la tabla CLIATC.CI_ACCIONES_IMF_MACRO.',
            ], 500);
        }
    }
}
