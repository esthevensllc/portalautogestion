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
Route::post('imei-cdr-automatico/generate', [\AMovil\Reports\General\BloqueoImei\Controllers\ImeiCRDAutomaticoController::class, 'generate']);
Route::post('imei-cdr-automatico/generateOnline', [\AMovil\Reports\General\BloqueoImei\Controllers\ImeiCRDAutomaticoController::class, 'generateOnline']);
Route::delete('rep-det-consumo/reportes-log', [\AMovil\Reports\RepDetLlamadas\Controllers\LogReporteLlamadasTempController::class, 'delete']);
Route::get('reporte-log', function (Request $request) {
    return DB::table("usraes.reporte_log")->orderBy("ini", "desc")->get();
    //return DB::connection("oracle_dbtodb")->table("TEMP_TAG_1460")->where('invoicenumber', '=','0264278286072022')->get();
});


