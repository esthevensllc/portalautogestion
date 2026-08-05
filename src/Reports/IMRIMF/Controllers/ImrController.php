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
            'input_placeholder' => 'Ej: 16805491 o H16805491',
            'default_search_type' => 'customer_id',
            'search_types' => [
                'customer_id' => [
                    'label' => 'Customer ID',
                    'placeholder' => 'Ej: 16805491 o H16805491',
                ],
                'telefono' => [
                    'label' => 'Teléfono',
                    'placeholder' => 'Ej: 902857695, 51902857695 o +51 902 857 695',
                ],
            ],
            'search_url' => rtrim(url()->current(), '/') . '/search',
            'initial_chart' => [
                'hasData' => false,
                'total' => 0,
                'saldo' => 0,
                'cargoFijo' => 0,
                'importeTotal' => 0,
                'cantidadAcciones' => 0,
                'cantidadFidelizaciones' => 0,
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
                'data' => $this->finder->search(
                    (string) $request->query('telefono', ''),
                    (string) $request->query('tipo_busqueda', 'customer_id')
                ),
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
                'message' => 'No se pudo consultar la información IMR. Revise la conexión, permisos sobre CLIATC.CI_PBI_ACCIONES_IMR, DWHDS.DS_SUSCRIPTORES y DWA.F_M_SEG_CLIENTES, o la existencia de la partición mensual.',
            ], 500);
        }
    }
}

