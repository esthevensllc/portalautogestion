<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('reporte-log', function (Request $request) {
    return DB::table("usraes.reporte_log")->orderBy("ini", "desc")->get();
    //return DB::connection("oracle_dbtodb")->table("TEMP_TAG_1460")->where('invoicenumber', '=','0264278286072022')->get();
});
Route::get('update', function (Request $request) {
	$cliente = "8.23098427.00.00.100000";
	$cliente = "8.20761657.00.00.100000";
	$fecha1 = DateTime::createFromFormat("Ymd", "20220607");
	$fecha2 = DateTime::createFromFormat("Ymd", "20220807");


    $data = DB::connection('oracle_dbtodb')->select(DB::raw("SELECT max(cycle) cycle FROM TEMP_TAG_11 WHERE CLIENTACCTNO in (?) and PERIODO = ?"), [$cliente, $fecha2->format("Ym")]);
        $ciclo = null;
        if(count($data)>0){
            $ciclo = $data[0]->cycle;
        }
//dd($ciclo );

        $fecha_ini = DateTime::createFromFormat("Y-m-d", $fecha1->format("Y-m")."-".$ciclo);
        // $fecha_fin = DateTime::createFromFormat("Y-m-d", $fecha2->format("Y-m")."-".$ciclo);
        $fecha_fin = (clone $fecha2);
        
	$periodos = [];
	//dd(['fecha' => $fecha1, 'fecha_periodo' => $fecha_ini]);
        /*if($fecha1->format("Ymd") >= $fecha_ini->format("Ymd")){
            $periodo = (clone $fecha_ini)->modify("-1 day");
            $periodos[] = $periodo->format("Ym");
        }*/
	//$periodo = (clone $fecha_ini)->modify("-1 day");
	//$periodo = $periodo->modify("+1 month");
	
        if($fecha1->format("Ymd") < $fecha_ini->format("Ymd")){
            $periodo = DateTime::createFromFormat("Y-m-d", $fecha1->format("Y-m")."-".$ciclo);
            //$periodo->modify("+1 month");
            //$periodo->modify("-1 day");
		//dd($periodo);
            $periodos[] = "primero: ".$periodo->format("Ym");
        }
        while ($fecha_ini->format("Ymd") <= $fecha_fin->format("Ymd")) {
            $periodo = (clone $fecha_ini)->modify("+1 month");
            //$periodo->modify("-1 day");
            $periodos[] = $periodo->format("Ym");
            $fecha_ini->modify("+1 month");
        }
	return ['fecha' => [$fecha1->format('Y-m-d'), $fecha2->format('Y-m-d')],'ciclo' => $ciclo,'periodos' => $periodos];
});

