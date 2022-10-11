<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('api/user', function (Request $request) {
    $auth_service = app(\AMovil\Auth\AccessControl\Domain\AuthService::class);
    $service = app(\AMovil\Auth\User\Services\GetUserModules::class);

    $username = $auth_service->getUserIdentifier();
    return [
        'username' => $username,
        'modules' => $service->__invoke($username)['tree'],
    ];
});

Route::group([
    'middleware' => 'auth.cas',
], function () {
    Route::get('/', function(){
        return redirect('dashboard');
    });
    Route::get('admin/logout', [\AMovil\Auth\AccessControl\Controllers\AccessControlController::class, 'logout']);
    Route::get('dashboard', [App\Http\Controllers\AdminController::class, 'dashboard']);
});

Route::group([
    //'prefix'     => config('backpack.base.route_prefix', 'admin'),
    /*'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),*/
    'middleware' => ['auth.cas', 'check.permission'],
    'namespace'  => 'App\Http\Controllers',
], function () {
    // SIGREI
    Route::get('reporte_sigrei', 'ReporteSigrei\ReporteSigreiController@index')->name('sigrei.reporte.index');
    Route::post('reporte_sigrei/import', 'ReporteSigrei\ReporteSigreiController@import');
    Route::get('reporte_sigrei/export', 'ReporteSigrei\ReporteSigreiController@export');
    Route::get('reporte_sigrei/test', 'ReporteSigrei\ReporteSigreiController@test');

    // detalle llamadas
    Route::get('rep-det-consumo/detalle-llamadas/entrantes', [\AMovil\Reports\RepDetLlamadas\Controllers\DetalleLLamadasController::class, 'entrantes']);
    Route::post('rep-det-consumo/detalle-llamadas/entrantes/export', [\AMovil\Reports\RepDetLlamadas\Controllers\DetalleLLamadasController::class, 'exportEntrantes']);
    Route::get('rep-det-consumo/detalle-llamadas/salientes', [\AMovil\Reports\RepDetLlamadas\Controllers\DetalleLLamadasController::class, 'salientes']);
    Route::post('rep-det-consumo/detalle-llamadas/salientes/export', [\AMovil\Reports\RepDetLlamadas\Controllers\DetalleLLamadasController::class, 'exportSalientes']);
    Route::get('rep-det-consumo/detalle-llamadas/entrantes-salientes', [\AMovil\Reports\RepDetLlamadas\Controllers\DetalleLLamadasController::class, 'entrantes_salientes']);
    Route::post('rep-det-consumo/detalle-llamadas/entrantes-salientes/export', [\AMovil\Reports\RepDetLlamadas\Controllers\DetalleLLamadasController::class, 'exportEntrantesSalientes']);

    Route::get('rep-det-consumo/detalle-llamadas/entrantes/test', function(){
        return ['access' => true];
    });

    //Reporte Facturacion fija
    Route::get('facturacion-fija', 'FacturacionFija\FacturacionFijaController@index')->name('facturacion.fija.index');
    Route::get('facturacion-fija/salientes/export', 'FacturacionFija\FacturacionFijaController@generar_reporte');
    Route::get('facturacion-fija/salientes/validReport', 'FacturacionFija\FacturacionFijaController@validReport');

    // reporte fiscalia
    Route::get('rep-fiscalia/rep-fiscal', [\AMovil\Reports\RepFiscalia\Controllers\ReporteFiscalController::class, 'repFiscal']);
    Route::get('rep-fiscalia/rep-fiscal/export', [\AMovil\Reports\RepFiscalia\Controllers\ReporteFiscalController::class, 'exportRepFiscal']);
    Route::get('rep-fiscalia/rep-fiscal/validator', [\AMovil\Reports\RepFiscalia\Controllers\ReporteFiscalController::class, 'validator']);

    Route::group(['prefix' => 'rep-det-consumo/detalle-consumo'], function(){
        Route::get('detallado', [AMovil\Reports\RepDetConsumo\Controllers\DetalleConsumoController::class, 'detallado']);
        Route::get('detallado/export', [AMovil\Reports\RepDetConsumo\Controllers\DetalleConsumoController::class, 'exportDetallado']);
        Route::get('detallado/validator', [AMovil\Reports\RepDetConsumo\Controllers\DetalleConsumoController::class, 'validation']);
        Route::get('consolidado', [AMovil\Reports\RepDetConsumo\Controllers\DetalleConsumoController::class, 'consolidado']);
        Route::get('consolidado/export', [AMovil\Reports\RepDetConsumo\Controllers\DetalleConsumoController::class, 'exportConsolidado']);
        Route::get('consolidado/validator', [AMovil\Reports\RepDetConsumo\Controllers\DetalleConsumoController::class, 'validation']);
    });
});