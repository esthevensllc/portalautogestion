use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

public function exportConsolidado(Request $request)
{
    ini_set('max_execution_time', '7200');
    set_time_limit(7200);

    // Generar el archivo Excel
    $export = $this->exportConsolidado->__invoke(
        $request->get('cod_cliente'),
        $request->get('periodo'),
        $request->get('unidad_trafico_id'),
        $request->get('unidad_consumo_id'),
        $request->get('consumo_sin_cargo'),
        $request->get('tipo_input'),
        $request->get('fecha1'),
        $request->get('fecha2')
    );

    // Guardar el archivo en una ruta específica en el servidor
    $fileName = 'CONSOLIDADO_DE_CONSUMO.xlsx';
    $filePath = 'exports/' . $fileName;

    Storage::disk('local')->put($filePath, $export);

    return response()->json([
        'success' => true,
        'path' => storage_path('app/' . $filePath)
    ]);
}