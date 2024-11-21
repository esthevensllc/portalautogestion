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

Route::group([
    'middleware' => ['auth.cas'],
], function () {
    Route::get('/', function(){
        return redirect('dashboard');
    });
    Route::get('admin/logout', [\AMovil\Auth\AccessControl\Controllers\AccessControlController::class, 'logout']);
    Route::get('dashboard', [App\Http\Controllers\AdminController::class, 'dashboard']);
    Route::get('general-import', [App\Http\Controllers\GeneralImportController::class, 'import_view']);
    Route::post('general-import/import', [App\Http\Controllers\GeneralImportController::class, 'import']);
});

Route::group([
    'middleware' => ['web','auth.cas', 'amovil.limit_sessions', 'check.permission', 'amovil.access_log'],
    'namespace'  => 'App\Http\Controllers',
], function () {
    Route::get('lineas-mtc-osiptel/logs/tickets/{tipo_plan}/{tipo_solicitud}', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'getTickets']);
    Route::get('lineas-mtc-osiptel/logsMsisdn/tickets/{tipo_plan}', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'getTicketsMsisdn']);
});

Route::group([
    //'prefix'     => config('backpack.base.route_prefix', 'admin'),
    /*'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),*/
    'middleware' => ['web','auth.cas', 'amovil.limit_sessions', 'check.permission', 'amovil.access_log'],
    'namespace'  => 'App\Http\Controllers',
], function () {
    // SIGREI
    Route::group(['prefix' => 'reporte_sigrei', 'trac_name' => 'sigrei'], function(){
        Route::get('/', 'ReporteSigrei\ReporteSigreiController@index')->name('sigrei.reporte.index');
        Route::post('import', 'ReporteSigrei\ReporteSigreiController@import');
        Route::get('export', 'ReporteSigrei\ReporteSigreiController@export');
        Route::get('test', 'ReporteSigrei\ReporteSigreiController@test');
    });

    // detalle llamadas
    Route::group(['prefix' => 'rep-det-consumo/detalle-llamadas/entrantes', 'trac_name' => 'detalle_llamadas.entrantes'], function(){
        Route::get('/', [\AMovil\Reports\RepDetLlamadas\Controllers\DetalleLLamadasController::class, 'entrantes']);
        Route::post('export', [\AMovil\Reports\RepDetLlamadas\Controllers\DetalleLLamadasController::class, 'exportEntrantes']);
    });
    Route::group(['prefix' => 'rep-det-consumo/detalle-llamadas/salientes', 'trac_name' => 'detalle_llamadas.salientes'], function(){
        Route::get('/', [\AMovil\Reports\RepDetLlamadas\Controllers\DetalleLLamadasController::class, 'salientes']);
        Route::post('export', [\AMovil\Reports\RepDetLlamadas\Controllers\DetalleLLamadasController::class, 'exportSalientes']);
    });
    Route::group(['prefix' => 'rep-det-consumo/detalle-llamadas/entrantes-salientes', 'trac_name' => 'detalle_llamadas.entrantes_salientes'], function(){
        Route::get('/', [\AMovil\Reports\RepDetLlamadas\Controllers\DetalleLLamadasController::class, 'entrantes_salientes']);
        Route::post('export', [\AMovil\Reports\RepDetLlamadas\Controllers\DetalleLLamadasController::class, 'exportEntrantesSalientes']);
    });
    Route::group(['prefix' => 'rep-det-consumo/reportes-log', 'trac_name' => 'detalle_llamadas.reporte_log'], function(){
        Route::get('/', [\AMovil\Reports\RepDetLlamadas\Controllers\LogReporteLlamadasTempController::class, 'view']);
        Route::get('search', [\AMovil\Reports\RepDetLlamadas\Controllers\LogReporteLlamadasTempController::class, 'search']);
    });

    //Reporte Facturacion fija
    Route::group(['prefix' => 'facturacion-fija', 'trac_name' => 'facturacion_fija.entrantes'], function(){
        Route::get('/', 'FacturacionFija\FacturacionFijaController@index')->name('facturacion.fija.index');
        Route::get('salientes/export', 'FacturacionFija\FacturacionFijaController@generar_reporte');
        Route::get('salientes/validReport', 'FacturacionFija\FacturacionFijaController@validReport');
    });

    // reporte fiscalia
    Route::group(['prefix' => 'rep-fiscalia/rep-fiscal', 'trac_name' => 'repote_fiscal.reporte_fiscal'], function(){
        Route::get('/', [\AMovil\Reports\RepFiscalia\Controllers\ReporteFiscalController::class, 'repFiscal']);
        Route::get('export', [\AMovil\Reports\RepFiscalia\Controllers\ReporteFiscalController::class, 'exportRepFiscal']);
        Route::get('validator', [\AMovil\Reports\RepFiscalia\Controllers\ReporteFiscalController::class, 'validator']);
    });

    Route::group(['prefix' => 'rep-det-consumo/detalle-consumo', 'trac_name' => 'detallle_consumo.detallado'], function(){
        Route::get('detallado', [AMovil\Reports\RepDetConsumo\Controllers\DetalleConsumoController::class, 'detallado']);
        Route::get('detallado/export', [AMovil\Reports\RepDetConsumo\Controllers\DetalleConsumoController::class, 'exportDetallado']);
        Route::get('detallado/validator', [AMovil\Reports\RepDetConsumo\Controllers\DetalleConsumoController::class, 'validation']);
        Route::get('detallado/clientes', [AMovil\Reports\RepDetConsumo\Controllers\DetalleConsumoController::class, 'clienteValidator']);
    });
    Route::group(['prefix' => 'rep-det-consumo/detalle-consumo', 'trac_name' => 'detallado_consumo.consolidado'], function(){
        Route::get('consolidado', [AMovil\Reports\RepDetConsumo\Controllers\DetalleConsumoController::class, 'consolidado']);
        Route::get('consolidado/export', [AMovil\Reports\RepDetConsumo\Controllers\DetalleConsumoController::class, 'exportConsolidado']);
        Route::get('consolidado/validator', [AMovil\Reports\RepDetConsumo\Controllers\DetalleConsumoController::class, 'validation']);
        Route::get('consolidado/clientes', [AMovil\Reports\RepDetConsumo\Controllers\DetalleConsumoController::class, 'clienteValidator']);
    });
    Route::group(['prefix' => 'rep-det-consumo/detalle-consumo', 'trac_name' => 'detalle_consumo.facturacion'], function(){
        Route::get('facturacion', [\AMovil\Reports\General\FacturacionAdelantada\Controllers\FacturacionAdelantadaController::class, 'view']);
        Route::post('facturacion/export', [\AMovil\Reports\General\FacturacionAdelantada\Controllers\FacturacionAdelantadaController::class, 'export']);
    });
    Route::group(['prefix' => 'rep-det-consumo/facturacion-consolidado', 'trac_name' => 'detalle_consumo.fa_consolidado'], function(){
        Route::get('/', [\AMovil\Reports\General\FacturacionAdelantada\Controllers\FacturacionAdelantadaController::class, 'consolidadoView']);
        Route::post('export', [\AMovil\Reports\General\FacturacionAdelantada\Controllers\FacturacionAdelantadaController::class, 'exportConsolidado']);
    });
    Route::group(['prefix' => 'rep-det-consumo/detalle-tasado', 'trac_name' => 'rep-det-consumo.detalle-tasado'], function(){
        Route::get('/', [\AMovil\Reports\DetalleTasado\Controllers\DetalleTasadoController::class, 'view']);
        Route::post('/export', [\AMovil\Reports\DetalleTasado\Controllers\DetalleTasadoController::class, 'export']);
    });
    Route::group(['prefix' => 'rep-det-consumo/nf/detallado', 'trac_name' => 'rep-det-consumo.nf.detallado'], function(){
        Route::get('/', [\AMovil\Reports\RepDetConsumoNF\Controllers\RepDetConsumoNFController::class, 'view']);
        Route::post('/export', [\AMovil\Reports\RepDetConsumoNF\Controllers\RepDetConsumoNFController::class, 'export']);
    });
    Route::group(['prefix' => 'rep-det-consumo/detalle-consumo/reportes', 'trac_name' => 'rep-det-consumo.detalle-consumo.reportes'], function(){
        Route::get('/', [\AMovil\Reports\RepDetConsumo\Controllers\DetalleConsumoController::class, 'viewReportes']);
        Route::get('/descarga-reportes', [\AMovil\Reports\RepDetConsumo\Controllers\DetalleConsumoController::class, 'descargaReportes']);
    });

    // admin
    Route::group(['prefix' => 'admin', 'trac_name' => 'admin.usuarios'], function(){
        // Route::get('usuarios', [\AMovil\Auth\User\Controllers\BackpackListUserController::class, 'index']);
        // Route::post('usuarios/search', [\AMovil\Auth\User\Controllers\BackpackListUserController::class, 'search']);
        Route::get('usuarios', [\AMovil\Auth\User\Controllers\ListUsersController::class, 'view']);
        Route::post('usuarios/search', [\AMovil\Auth\User\Controllers\ListUsersController::class, 'get']);
        Route::get('usuarios/edit/{id}', [\AMovil\Auth\User\Controllers\EditUserController::class, 'view']);
        Route::post('usuarios/edit/{id}', [\AMovil\Auth\User\Controllers\EditUserController::class, 'udpate']);
        Route::get('usuarios/create', [\AMovil\Auth\User\Controllers\CreateUserController::class, 'view']);
        Route::post('usuarios/create', [\AMovil\Auth\User\Controllers\CreateUserController::class, 'create']);
        Route::post('usuarios/{id}/status/{status}', [\AMovil\Auth\User\Controllers\ChangeUserStatusController::class, '__invoke']);
        Route::get('usuarios/{id}', [\AMovil\Auth\User\Controllers\FindUserController::class, 'view']);
        Route::post('usuarios/{id}/eliminar', [\AMovil\Auth\User\Controllers\DeleteUserController::class, '__invoke']);
    });
    Route::group(['prefix' => 'admin/roles', 'trac_name' => 'admin.roles'], function(){
        Route::get('/', [\AMovil\Auth\Roles\Controllers\GetRolesController::class, 'view']);
        Route::post('search', [\AMovil\Auth\Roles\Controllers\GetRolesController::class, 'get']);
        Route::get('edit/{id}', [\AMovil\Auth\Roles\Controllers\UpdateRolController::class, 'view']);
        Route::post('edit/{id}', [\AMovil\Auth\Roles\Controllers\UpdateRolController::class, 'update']);
        Route::post('{id}/status/{status}', [\AMovil\Auth\Roles\Controllers\ChangeRolStatusController::class, '__invoke']);
        Route::get('create', [\AMovil\Auth\Roles\Controllers\CreateRolController::class, 'view']);
        Route::post('/', [\AMovil\Auth\Roles\Controllers\CreateRolController::class, 'create']);
    });
    
    Route::group(['prefix' => 'mtc/suspensiones', 'trac_name' => 'mtc.suspensiones'], function(){
        Route::get('/', [\AMovil\Reports\Mtc\Suspensiones\Controllers\MtcSuspensionesController::class, 'view']);
        Route::post('/export', [\AMovil\Reports\Mtc\Suspensiones\Controllers\MtcSuspensionesController::class, 'export']);
        Route::get('/export-test', [\AMovil\Reports\Mtc\Suspensiones\Controllers\MtcSuspensionesController::class, 'exportTest']);
    });

    Route::group(['prefix' => 'reporte-cursados', 'trac_name' => 'reporte-cursados'], function(){
        Route::get('/', [\AMovil\Reports\RepCursado\Controllers\ReporteCursadoController::class, 'view']);
        Route::post('/export', [\AMovil\Reports\RepCursado\Controllers\ReporteCursadoController::class, 'export']);
    });

    // DAPU
    Route::group(['prefix' => 'dapu/tramites-consulta', 'trac_name' => 'dapu.tramites-consulta'], function(){
        Route::get('/', [\AMovil\Reports\DAPU\TramiteConsulta\Controllers\TramiteConsultaController::class, 'view']);
        Route::post('export', [\AMovil\Reports\DAPU\TramiteConsulta\Controllers\TramiteConsultaController::class, 'export']);
    });
    Route::group(['prefix' => 'dapu/suspension-servicio', 'trac_name' => 'dapu.suspension-servicio'], function(){
        Route::get('/', [\AMovil\Reports\DAPU\SuspensionServicio\Controllers\SuspensionServicioController::class, 'view']);
        Route::get('/json', [\AMovil\Reports\DAPU\SuspensionServicio\Controllers\SuspensionServicioController::class, 'getData']);
        Route::get('/export', [\AMovil\Reports\DAPU\SuspensionServicio\Controllers\SuspensionServicioController::class, 'export']);
    });
    Route::group(['prefix' => 'dapu/adquisicion', 'trac_name' => 'dapu.adquisicion'], function(){
        Route::get('/', [\AMovil\Reports\DAPU\Adquisiciones\Controllers\AdquisicionEquipoController::class, 'view']);
        Route::post('json', [\AMovil\Reports\DAPU\Adquisiciones\Controllers\AdquisicionEquipoController::class, 'getData']);
        Route::post('export', [\AMovil\Reports\DAPU\Adquisiciones\Controllers\AdquisicionEquipoController::class, 'export']);
    });
    Route::group(['prefix' => 'dapu/consulta-linea', 'trac_name' => 'dapu.consulta-linea'], function(){
        Route::get('/', [\AMovil\Reports\DAPU\Lineas\Controllers\LineaController::class, 'view']);
        Route::post('/json', [\AMovil\Reports\DAPU\Lineas\Controllers\LineaController::class, 'getData']);
        Route::post('/export', [\AMovil\Reports\DAPU\Lineas\Controllers\LineaController::class, 'export']);
    });
    Route::group(['prefix' => 'dapu/consulta-fono', 'trac_name' => 'dapu.consulta-fono'], function(){
        Route::get('/', [\AMovil\Reports\DAPU\FONO\Controllers\FonoController::class, 'view']);
        Route::get('/json', [\AMovil\Reports\DAPU\FONO\Controllers\FonoController::class, 'getData']);
        Route::post('/export', [\AMovil\Reports\DAPU\FONO\Controllers\FonoController::class, 'export']);
    });
    Route::group(['prefix' => 'dapu/ventas-linea', 'trac_name' => 'dapu.ventas-linea'], function(){
        Route::get('/', [\AMovil\Reports\DAPU\VentasLinea\Controllers\VentasLineaController::class, 'view']);
        Route::get('/json', [\AMovil\Reports\DAPU\VentasLinea\Controllers\VentasLineaController::class, 'getData']);
        Route::post('/export', [\AMovil\Reports\DAPU\VentasLinea\Controllers\VentasLineaController::class, 'export']);
    });
    Route::group(['prefix' => 'dapu/log-biometria', 'trac_name' => 'dapu.log-biometria'], function(){
        Route::get('/', [\AMovil\Reports\DAPU\LogBiometria\Controllers\LogBiometriaController::class, 'view']);
        Route::get('/json', [\AMovil\Reports\DAPU\LogBiometria\Controllers\LogBiometriaController::class, 'getData']);
        Route::post('/export', [\AMovil\Reports\DAPU\LogBiometria\Controllers\LogBiometriaController::class, 'export']);
    });
    Route::group(['prefix' => 'dapu/equipo-biometria', 'trac_name' => 'dapu.equipo-biometria'], function(){
        Route::get('/', [\AMovil\Reports\DAPU\EquipoBiometria\Controllers\EquipoBiometriaController::class, 'view']);
        Route::get('/json', [\AMovil\Reports\DAPU\EquipoBiometria\Controllers\EquipoBiometriaController::class, 'getData']);
        Route::post('/export', [\AMovil\Reports\DAPU\EquipoBiometria\Controllers\EquipoBiometriaController::class, 'export']);
    });
    Route::group(['prefix' => 'dapu/historico-bloqueos', 'trac_name' => 'dapu.historico-bloqueos'], function(){
        Route::get('/', [\AMovil\Reports\DAPU\HistoricoBloqueos\Controllers\HistoricoBloqueoController::class, 'view']);
        Route::get('/json', [\AMovil\Reports\DAPU\HistoricoBloqueos\Controllers\HistoricoBloqueoController::class, 'getData']);
        Route::post('/export', [\AMovil\Reports\DAPU\HistoricoBloqueos\Controllers\HistoricoBloqueoController::class, 'export']);
    });
    Route::group(['prefix' => 'dapu/lista-eir', 'trac_name' => 'dapu.lista-eir'], function(){
        Route::get('/', [\AMovil\Reports\DAPU\ListaEir\Controllers\ListaEirController::class, 'view']);
        Route::get('/json', [\AMovil\Reports\DAPU\ListaEir\Controllers\ListaEirController::class, 'getData']);
        Route::post('/export', [\AMovil\Reports\DAPU\ListaEir\Controllers\ListaEirController::class, 'export']);
    });
    Route::group(['prefix' => 'dapu/motivos-bloqueo-desbloqueo', 'trac_name' => 'dapu.motivos-bloqueo-desbloqueo'], function(){
        Route::get('/', [\AMovil\Reports\DAPU\MotivosBloqueoDesbloqueo\Controllers\MotivosBloqueoDesbloqueoController::class, 'view']);
        Route::get('/json', [\AMovil\Reports\DAPU\MotivosBloqueoDesbloqueo\Controllers\MotivosBloqueoDesbloqueoController::class, 'getData']);
        Route::post('/export', [\AMovil\Reports\DAPU\MotivosBloqueoDesbloqueo\Controllers\MotivosBloqueoDesbloqueoController::class, 'export']);
    });
    Route::group(['prefix' => 'dapu/servicio-movil', 'trac_name' => 'dapu.servicio-movil'], function(){
        Route::get('/', [\AMovil\Reports\DAPU\ServicioMovil\Controllers\ServicioMovilController::class, 'view']);
        Route::get('/json', [\AMovil\Reports\DAPU\ServicioMovil\Controllers\ServicioMovilController::class, 'getData']);
        Route::post('/export', [\AMovil\Reports\DAPU\ServicioMovil\Controllers\ServicioMovilController::class, 'export']);
    });
    Route::group(['prefix' => 'dapu/lista-excepciones-imei-imsi', 'trac_name' => 'dapu.lista-excepciones-imei-imsi'], function(){
        Route::get('/', [\AMovil\Reports\DAPU\ListaExcepcionesImeiImsi\Controllers\ListaExcepcionesImeiImsiController::class, 'view']);
        Route::get('/json', [\AMovil\Reports\DAPU\ListaExcepcionesImeiImsi\Controllers\ListaExcepcionesImeiImsiController::class, 'getData']);
        Route::post('/export', [\AMovil\Reports\DAPU\ListaExcepcionesImeiImsi\Controllers\ListaExcepcionesImeiImsiController::class, 'export']);
    });

    Route::group(['prefix' => 'rep-recargas/detalle', 'trac_name' => 'rep-recargas.detalle'], function(){
        Route::get('/', [\AMovil\Reports\RepRecargas\Controllers\ReporteRecargasController::class, 'detalle']);
        Route::post('/export', [\AMovil\Reports\RepRecargas\Controllers\ReporteRecargasController::class, 'exportDetalle']);
    });
    Route::group(['prefix' => 'rep-recargas/extras', 'trac_name' => 'rep-recargas.extras'], function(){
        Route::get('/', [\AMovil\Reports\RepRecargas\Controllers\ReporteRecargasController::class, 'extras']);
        Route::post('/export', [\AMovil\Reports\RepRecargas\Controllers\ReporteRecargasController::class, 'exportExtras']);
    });
    Route::group(['prefix' => 'logs/reporte_log', 'trac_name' => 'logs.reporte_log'], function(){
        Route::get('/', [\AMovil\Reports\ReportLog\Controllers\GetReportLogController::class, 'view']);
        Route::post('/search', [\AMovil\Reports\ReportLog\Controllers\GetReportLogController::class, 'search']);
    });
    Route::group(['prefix' => 'minedu/consumo-activacion', 'trac_name' => 'minedu.consumo-activacion'], function(){
        Route::get('/', [\AMovil\Reports\MINEDU\RepConsumo\Controllers\ReporteConsumoController::class, 'view']);
        Route::post('/export', [\AMovil\Reports\MINEDU\RepConsumo\Controllers\ReporteConsumoController::class, 'export']);
    });
    Route::group(['prefix' => 'visanet/visanet', 'trac_name' => 'visanet.visanet'], function(){
        Route::get('/', [\AMovil\Reports\Visanet\Controllers\VisanetController::class, 'view']);
        Route::post('/export', [\AMovil\Reports\Visanet\Controllers\VisanetController::class, 'export']);
    });
    Route::group(['prefix' => 'caja-arequipa/gb-otorgados', 'trac_name' => 'caja-arequipa.gb-otorgados'], function(){
        Route::get('/', [\AMovil\Reports\CajaArequipa\GBOtorgados\Controllers\GBOtorgadosController::class, 'view']);
        Route::post('/export', [\AMovil\Reports\CajaArequipa\GBOtorgados\Controllers\GBOtorgadosController::class, 'export']);
    });
    Route::group(['prefix' => 'caja-arequipa/factura-detallada', 'trac_name' => 'caja-arequipa.factura-detallada'], function(){
        Route::get('/', [\AMovil\Reports\CajaArequipa\FacturaDetallada\Controllers\FacturaDetalladaController::class, 'view']);
        Route::post('/export', [\AMovil\Reports\CajaArequipa\FacturaDetallada\Controllers\FacturaDetalladaController::class, 'export']);
    });

    Route::group(['prefix' => 'extraccion-devolucion', 'trac_name' => 'extraccion-devolucion'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Controllers\ExtraccionDevolucionController::class, 'view']);
        Route::post('/process', [\AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Controllers\ExtraccionDevolucionController::class, 'process']);
    });
    Route::group(['prefix' => 'extraccion-devolucion/tabla-interes', 'trac_name' => 'extraccion-devolucion.tabla-interes'], function(){
        Route::post('api', [\AMovil\Reports\ExtraccionDevolucion\TablaInteres\Controllers\TablaInteresController::class, 'create']);
        Route::get('api', [\AMovil\Reports\ExtraccionDevolucion\TablaInteres\Controllers\TablaInteresController::class, 'api']);
        Route::get('/', [\AMovil\Reports\ExtraccionDevolucion\TablaInteres\Controllers\TablaInteresController::class, 'view']);
    });
    Route::group(['prefix' => 'extraccion-devolucion/reportes', 'trac_name' => 'extraccion-devolucion.reportes'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Controllers\ExtraccionDevolucionController::class, 'reportView']);
        Route::post('export', [\AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Controllers\ExtraccionDevolucionController::class, 'export']);
        Route::post('/confirm', [\AMovil\Reports\ExtraccionDevolucion\TicketReports\Controllers\TicketReportsController::class, 'confirm']);
        Route::post('/find-input', [\AMovil\Reports\ExtraccionDevolucion\TicketReports\Controllers\TicketReportsController::class, 'findInput']);
    });
    Route::group(['prefix' => 'extraccion-devolucion/tickets', 'trac_name' => 'extraccion-devolucion.tickets'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevolucion\TicketReports\Controllers\TicketReportsController::class, 'view']);
        Route::post('search', [\AMovil\Reports\ExtraccionDevolucion\TicketReports\Controllers\TicketReportsController::class, 'search']);
    });
    Route::group(['prefix' => 'extraccion-devolucion/eliminar-ticket', 'trac_name' => 'extraccion-devolucion.eliminar-ticket'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevolucion\TicketReports\Controllers\TicketReportsController::class, 'deleteView']);
        Route::post('/', [\AMovil\Reports\ExtraccionDevolucion\TicketReports\Controllers\TicketReportsController::class, 'delete']);
    });
    Route::group(['prefix' => 'extraccion-devolucion/carga-reportes', 'trac_name' => 'extraccion-devolucion.carga-reportes'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Controllers\ExtraccionDevolucionController::class, 'updateView']);
        Route::post('/import', [\AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Controllers\ExtraccionDevolucionController::class, 'import']);
    });
    Route::group(['prefix' => 'extraccion-devolucion/cartera-gobierno', 'trac_name' => 'extraccion-devolucion.cartera-gobierno'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevolucion\CarteraGobierno\Controllers\CarteraGobiernoController::class, 'view']);
        Route::post('/import', [\AMovil\Reports\ExtraccionDevolucion\CarteraGobierno\Controllers\CarteraGobiernoController::class, 'import']);
    });
    Route::group(['prefix' => 'extraccion-devolucion/modev', 'trac_name' => 'extraccion-devolucion.modev'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevolucion\MODEV\Controllers\MODEVController::class, 'view']);
        Route::post('/export', [\AMovil\Reports\ExtraccionDevolucion\MODEV\Controllers\MODEVController::class, 'export']);
    });
    Route::group(['prefix' => 'extraccion-devolucion/carga-informe-fallas', 'trac_name' => 'extraccion-devolucion.carga-info-fallas'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'view']);
        Route::post('/import', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'import']);
        Route::get('/search', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'get']);
        Route::get('/search/{id}', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'findInput']);
        Route::get('/{id}', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'reportView']);
        Route::post('/delete/{id}', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'delete']);
        Route::post('/aprobar', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'aprobar']);
        Route::post('/desaprobar', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'desaprobar']);
        Route::post('/en-espera', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'enEspera']);
        Route::post('/revisado', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'revisado']);
    });
    Route::group(['prefix' => 'extraccion-devolucion/carga-informe-fallas-msisdn', 'trac_name' => 'extraccion-devolucion.carga-info-fallas-msisdn'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaMsisdnController::class, 'view']);
        Route::post('/import', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaMsisdnController::class, 'import']);
        Route::get('/search', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'get']);
        Route::get('/search/{id}', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'findInput']);
        Route::get('/{id}', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'reportView']);
        Route::post('/delete/{id}', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'delete']);
        Route::post('/aprobar', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'aprobar']);
        Route::post('/desaprobar', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'desaprobar']);
        Route::post('/en-espera', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'enEspera']);
        Route::post('/revisado', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'revisado']);
    });
    Route::group(['prefix' => 'extraccion-devolucion/usuario-minuto', 'trac_name' => 'extraccion-devolucion.usuario-minuto'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\UsuarioMinutosController::class, 'view']);
        Route::get('departamentos', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\UsuarioMinutosController::class, 'getDepartamentosByNumReporte']);
        Route::get('usuarios-minutos', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\UsuarioMinutosController::class, 'getUsuariosByNumReporte']);
        Route::get('usuarios-minutos-calculados', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\UsuarioMinutosController::class, 'getUsuariosByMinutos']);
        Route::post('process', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\UsuarioMinutosController::class, 'processExtraccion']);
        Route::post('reporte-msisdn/export', [\AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Controllers\ExtraccionDevolucionController::class, 'exportReporteMsisdn']);
    });
    Route::group(['prefix' => 'extraccion-devolucion/informe-fallas', 'trac_name' => 'extraccion-devolucion.informe-fallas'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\InformeFallasController::class, 'view']);
        Route::get('search', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'get']);
        Route::get('search/n-reporte/{id}', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'findInput']);
        Route::get('search/ticket/{ticket}', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'getReportesByTicket']);
        Route::post('/delete/{id}', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'delete']);
        Route::post('/aprobar', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'aprobar']);
        Route::post('/desaprobar', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'desaprobar']);
        Route::post('/en-espera', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'enEspera']);
        Route::post('/revisado', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'revisado']);
        Route::post('/update-status', [\AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Controllers\CargaInformeFallaController::class, 'updateReportStatus']);
    });
    Route::group(['prefix' => 'extraccion-devolucion/informes-ccpp', 'trac_name' => 'extraccion-devolucion.informes-ccpp'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Controllers\InformeCCPPController::class, 'view']);
        Route::post('import', [\AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Controllers\InformeCCPPController::class, 'import']);
        Route::get('search', [\AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Controllers\InformeCCPPController::class, 'search']);
        Route::get('{id}/download', [\AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Controllers\InformeCCPPController::class, 'download']);
    });

    Route::group(['prefix' => 'michaell-cia-nn', 'trac_name' => 'michaell-cia-nn'], function(){
        Route::get('/', [\AMovil\Reports\General\RepMichaellCIANN\Controllers\RepMichaellCIANNController::class, 'view']);
        Route::get('/num-cuenta-info/{numCuenta}', [\AMovil\Reports\General\RepMichaellCIANN\Controllers\RepMichaellCIANNController::class, 'getInfoForNumCuenta']);
        Route::post('/export', [\AMovil\Reports\General\RepMichaellCIANN\Controllers\RepMichaellCIANNController::class, 'export']);
    });

    Route::group(['prefix' => 'extraccion-dev-fija', 'trac_name' => 'extraccion-dev-fija'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Controllers\ExtraccionDevFijaController::class, 'view']);
        Route::post('/process', [\AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Controllers\ExtraccionDevFijaController::class, 'process']);
    });
    Route::group(['prefix' => 'extraccion-dev-fija/reportes', 'trac_name' => 'extraccion-dev-fija.reportes'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Controllers\ExtraccionDevFijaController::class, 'reportView']);
        Route::post('find-input', [\AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Controllers\ExtraccionDevFijaController::class, 'findInput']);
        Route::post('export', [\AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Controllers\ExtraccionDevFijaController::class, 'export']);
    });
    Route::group(['prefix' => 'extraccion-dev-fija/tickets', 'trac_name' => 'extraccion-dev-fija.tickets'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevFija\TicketReports\Controllers\FijaTicketReportController::class, 'view']);
        Route::get('search', [\AMovil\Reports\ExtraccionDevFija\TicketReports\Controllers\FijaTicketReportController::class, 'search']);
    });
    Route::group(['prefix' => 'extraccion-dev-fija/tickets/eliminar', 'trac_name' => 'extraccion-dev-fija.eliminar'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevFija\TicketReports\Controllers\FijaTicketReportController::class, 'deleteView']);
        Route::post('', [\AMovil\Reports\ExtraccionDevFija\TicketReports\Controllers\FijaTicketReportController::class, 'deleteTicket']);
    });
    Route::group(['prefix' => 'extraccion-dev-fija/informes-falla/cargar', 'trac_name' => 'extraccion-dev-fija.informes-falla.create'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevFija\InformesFalla\Controllers\InformeFallasController::class, 'createView']);
        Route::post('/', [\AMovil\Reports\ExtraccionDevFija\InformesFalla\Controllers\InformeFallasController::class, 'create']);
        Route::get('search', [\AMovil\Reports\ExtraccionDevFija\InformesFalla\Controllers\InformeFallasController::class, 'search']);
        Route::post('{numReporte}/{servicioAfectadoId}/delete', [\AMovil\Reports\ExtraccionDevFija\InformesFalla\Controllers\InformeFallasController::class, 'delete']);
        Route::get('{numReporte}/download', [\AMovil\Reports\ExtraccionDevFija\InformesFalla\Controllers\InformeFallasController::class, 'download']);
    });
    Route::group(['prefix' => 'extraccion-dev-fija/informes-falla', 'trac_name' => 'extraccion-dev-fija.informes-falla'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevFija\InformesFalla\Controllers\InformeFallasController::class, 'view']);
        Route::get('search', [\AMovil\Reports\ExtraccionDevFija\InformesFalla\Controllers\InformeFallasController::class, 'search']);
        Route::post('update-status', [\AMovil\Reports\ExtraccionDevFija\InformesFalla\Controllers\InformeFallasController::class, 'updateStatus']);
        Route::post('{numReporte}/{servicioAfectadoId}/delete', [\AMovil\Reports\ExtraccionDevFija\InformesFalla\Controllers\InformeFallasController::class, 'delete']);
        Route::get('{numReporte}/download', [\AMovil\Reports\ExtraccionDevFija\InformesFalla\Controllers\InformeFallasController::class, 'download']);
    });
    Route::group(['prefix' => 'extraccion-dev-fija/informes-falla/procesar', 'trac_name' => 'extraccion-dev-fija.informes-falla.process'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevFija\InformesFalla\Controllers\InformeFallasController::class, 'processView']);
        Route::post('/', [\AMovil\Reports\ExtraccionDevFija\InformesFalla\Controllers\InformeFallasController::class, 'processFromInput']);
    });
    Route::group(['prefix' => 'extraccion-dev-fija/carga-reportes', 'trac_name' => 'extraccion-dev-fija.carga-reportes'], function(){
        Route::get('/', [\AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Controllers\ExtraccionDevFijaController::class, 'cargaReporteView']);
        Route::post('/upload', [\AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Controllers\ExtraccionDevFijaController::class, 'uploadReport']);
    });

    Route::group(['prefix' => 'lineas-mtc-osiptel', 'trac_name' => 'lineas-mtc-osiptel'], function(){
        Route::get('/', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'view']);
        Route::post('/export', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'export']);
    });
    Route::group(['prefix' => 'lineas-mtc-osiptel/logs', 'trac_name' => 'lineas-mtc-osiptel.logs'], function(){
        Route::get('/', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'logView']);
        Route::post('/save', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'saveLog']);
        //Route::get('tickets/{tipo_plan}/{tipo_solicitud}', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'getTickets']);
    });
    Route::group(['prefix' => 'lineas-mtc-osiptel/registros-sms', 'trac_name' => 'lineas-mtc-osiptel.registros-sms'], function(){
        Route::get('/', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'registrosView']);
        Route::post('/search', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'search']);
    });
    Route::group(['prefix' => 'lineas-mtc-osiptel/registros-sms/eliminar', 'trac_name' => 'lineas-mtc-osiptel.registros-sms.delete'], function(){
        Route::get('/', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'deleteView']);
        Route::post('/', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'delete']);
    });
    Route::group(['prefix' => 'lineas-mtc-osiptel/osiptel-msisdn-dni', 'trac_name' => 'lineas-mtc-osiptel.osiptel-msisdn-dni'], function(){
        Route::get('/', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'cartaView']);
        Route::post('/export', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'procesarCarta']);
    });
    Route::group(['prefix' => 'lineas-mtc-osiptel/osiptel-registros-msisdn', 'trac_name' => 'lineas-mtc-osiptel.registros-msisdn'], function(){
        Route::get('/', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'registrosMsisdnView']);
        Route::post('/searchMsisdn', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'searchMsisdn']);
    });
    Route::group(['prefix' => 'lineas-mtc-osiptel/osiptel-registros-msisdn/eliminar', 'trac_name' => 'lineas-mtc-osiptel.registros-msisdn.delete'], function(){
        Route::get('/', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'deleteMsisdnView']);
        Route::post('/', [\AMovil\Reports\General\LineasMTCOsiptel\Controllers\LineasMTCOsiptelController::class, 'deleteMsisdn']);
    });

    // BASES CLIENTES PREPAGO
    Route::group(['prefix' => 'bases-clientes-prepago', 'trac_name' => 'bases-clientes-prepago'], function(){
        Route::get('/', [\AMovil\Reports\BasesClientesPrepago\Controllers\BasesClientesPrepagoController::class, 'view']);
        Route::get('/getUbigeo', [\AMovil\Reports\BasesClientesPrepago\Controllers\BasesClientesPrepagoController::class, 'getUbigeo']);
        Route::post('/export', [\AMovil\Reports\BasesClientesPrepago\Controllers\BasesClientesPrepagoController::class, 'export']);
    });

    // SOLICITUD DE DATOS/CONFIRMACIÓN
    Route::group(['prefix' => 'solicitud-datos-confirmacion', 'trac_name' => 'solicitud-datos-confirmacion'], function(){
        Route::get('/', [\AMovil\Reports\SolicitudDatosConfirmacion\Controllers\SolicitudDatosConfirmacionController::class, 'view']);
        Route::get('/getTable', [\AMovil\Reports\SolicitudDatosConfirmacion\Controllers\SolicitudDatosConfirmacionController::class, 'getTable'])->name('solicitud-datos-confirmacion.getTable');
        Route::post('/export', [\AMovil\Reports\SolicitudDatosConfirmacion\Controllers\SolicitudDatosConfirmacionController::class, 'export']);
    });

    // GEOMARKETING
    Route::group(['prefix' => 'geomarketing', 'trac_name' => 'geomarketing'], function(){
        Route::get('/', [\AMovil\Reports\GeoMarketing\Controllers\GeoMarketingController::class, 'view']);
        Route::post('/export', [\AMovil\Reports\GeoMarketing\Controllers\GeoMarketingController::class, 'export']);
    });
    Route::group(['prefix' => 'geomarketing/logs', 'trac_name' => 'geomarketing.logs'], function(){
        Route::get('/', [\AMovil\Reports\GeoMarketing\Controllers\GeoMarketingController::class, 'logView']);
        Route::get('search', [\AMovil\Reports\GeoMarketing\Controllers\GeoMarketingController::class, 'logSearch']);
    });

    Route::group(['prefix' => 'bloqueo-imei', 'trac_name' => 'bloqueo-imei'], function(){
        Route::get('/', [\AMovil\Reports\General\BloqueoImei\Controllers\BloqueoImeiController::class, 'view']);
        Route::get('search', [\AMovil\Reports\General\BloqueoImei\Controllers\BloqueoImeiController::class, 'search']);
        Route::post('/export', [\AMovil\Reports\General\BloqueoImei\Controllers\BloqueoImeiController::class, 'export']);
        Route::delete('reportlog/{id}', [\AMovil\Reports\General\BloqueoImei\Controllers\BloqueoImeiController::class, 'deleteReportlog']);
    });
    Route::group(['prefix' => 'imei-cdr-automatico', 'trac_name' => 'imei-cdr-automatico'], function(){
        Route::get('/', [\AMovil\Reports\General\BloqueoImei\Controllers\ImeiCRDAutomaticoController::class, 'view']);
        Route::get('search', [\AMovil\Reports\General\BloqueoImei\Controllers\ImeiCRDAutomaticoController::class, 'search']);
        Route::post('reportlog/base-imei', [\AMovil\Reports\General\BloqueoImei\Controllers\ImeiCRDAutomaticoController::class, 'importBaseImei']);
        Route::post('reportlog/base-imei/delete', [\AMovil\Reports\General\BloqueoImei\Controllers\ImeiCRDAutomaticoController::class, 'deleteBaseImei']);
        Route::delete('reportlog/{id}', [\AMovil\Reports\General\BloqueoImei\Controllers\ImeiCRDAutomaticoController::class, 'deleteReportlog']);
    });
    Route::group(['prefix' => 'imei-cdr-automatico/reports', 'trac_name' => 'imei-cdr-automatico.reports'], function(){
        Route::get('/', [\AMovil\Reports\General\BloqueoImei\Controllers\ImeiCRDAutomaticoController::class, 'reportsView']);
        Route::get('search', [\AMovil\Reports\General\BloqueoImei\Controllers\ImeiCRDAutomaticoController::class, 'reportSearch']);
        Route::get('{id}/download', [\AMovil\Reports\General\BloqueoImei\Controllers\ImeiCRDAutomaticoController::class, 'downloadReport']);
    });
    Route::group(['prefix' => 'imei-cdr-automatico/config', 'trac_name' => 'imei-cdr-automatico.config'], function(){
        Route::get('/', [\AMovil\Reports\General\BloqueoImei\Controllers\ImeiCDRConfigController::class, 'view']);
        Route::post('/', [\AMovil\Reports\General\BloqueoImei\Controllers\ImeiCDRConfigController::class, 'saveConfig']);
    });
    Route::group(['prefix' => 'control-eir-imei', 'trac_name' => 'control-eir-imei'], function(){
        Route::get('/', [\AMovil\Reports\General\BloqueoImei\Controllers\ControlEirImeiController::class, 'view']);
        Route::post('search', [\AMovil\Reports\General\BloqueoImei\Controllers\ControlEirImeiController::class, 'search']);
    });
    Route::group(['prefix' => 'control-eir-imei2', 'trac_name' => 'control-eir-imei2'], function(){
        Route::get('/', [\AMovil\Reports\General\BloqueoImei\Controllers\ControlEirImeiController::class, 'view']);
        Route::post('search', [\AMovil\Reports\General\BloqueoImei\Controllers\ControlEirImeiController::class, 'search']);
    });
    Route::group(['prefix' => 'imeis-cdr-report-online', 'trac_name' => 'imeis-cdr-report-online'], function(){
        Route::get('/', [\AMovil\Reports\General\BloqueoImei\Controllers\ImeiCRDAutomaticoController::class, 'reportsOnlineView']);
        Route::get('search', [\AMovil\Reports\General\BloqueoImei\Controllers\ImeiCRDAutomaticoController::class, 'reportOnlineSearch']);
        Route::get('{id}/download', [\AMovil\Reports\General\BloqueoImei\Controllers\ImeiCRDAutomaticoController::class, 'downloadOnlineReport']);
    });
    Route::group(['prefix' => 'tripleta', 'trac_name' => 'tripleta.find'], function(){
        Route::get('/', [\AMovil\Reports\General\BloqueoImei\Controllers\TripletaController::class, 'view']);
        Route::get('search', [\AMovil\Reports\General\BloqueoImei\Controllers\TripletaController::class, 'search']);
        Route::post('search-by-file', [\AMovil\Reports\General\BloqueoImei\Controllers\TripletaController::class, 'searchByFile']);
    });
    Route::group(['prefix' => 'comprobante-pago', 'trac_name' => 'comprobante-pago'], function(){
        Route::get('/', [\AMovil\Reports\General\ComprobantePago\Controllers\ComprobantePagoController::class, 'view']);
        Route::post('export', [\AMovil\Reports\General\ComprobantePago\Controllers\ComprobantePagoController::class, 'export']);
        Route::get('export-template', [\AMovil\Reports\General\ComprobantePago\Controllers\ComprobantePagoController::class, 'exportTemplate']);
    });

    Route::group(['prefix' => 'bloqueo-control-regulatorio', 'trac_name' => 'bloqueo-control-regulatorio'], function(){
        Route::get('/', [\AMovil\Reports\BloqueoControlReg\Controllers\BloqueoControlRegController::class, 'cargarView']);
        Route::post('import', [\AMovil\Reports\BloqueoControlReg\Controllers\BloqueoControlRegController::class, 'import']);
        // Route::get('{id}/download', [\AMovil\Reports\BloqueoControlReg\Controllers\BloqueoControlRegController::class, 'downloadDocument']);
    });
    Route::group(['prefix' => 'bloqueo-control-regulatorio/logs', 'trac_name' => 'bloqueo-control-regulatorio.logs'], function(){
        Route::get('/', [\AMovil\Reports\BloqueoControlReg\Controllers\BloqueoControlRegLogController::class, 'view']);
        Route::get('search', [\AMovil\Reports\BloqueoControlReg\Controllers\BloqueoControlRegLogController::class, 'getData']);
        Route::get('{id}/download', [\AMovil\Reports\BloqueoControlReg\Controllers\BloqueoControlRegController::class, 'downloadDocument']);
        Route::get('{id}/download-eir', [\AMovil\Reports\BloqueoControlReg\Controllers\BloqueoControlRegController::class, 'downloadEir']);
    });
    Route::group(['prefix' => 'detalle-planes', 'trac_name' => 'detalle-planes'], function(){
        Route::get('/', [\AMovil\Reports\DetallePlanes\Planes\Controllers\DetallePlanController::class, 'view']);
        Route::post('export', [\AMovil\Reports\DetallePlanes\Planes\Controllers\DetallePlanController::class, 'export']);
    });
    Route::group(['prefix' => 'detalle-planes/facturacion-detallada', 'trac_name' => 'detalle-planes.facturacion-detallada'], function(){
        Route::get('/', [\AMovil\Reports\DetallePlanes\FacturacionDetallada\Controllers\FacturacionDetalladaController::class, 'view']);
        Route::post('export', [\AMovil\Reports\DetallePlanes\FacturacionDetallada\Controllers\FacturacionDetalladaController::class, 'export']);
    });
    Route::group(['prefix' => 'detalle-planes/consolidado-minutos', 'trac_name' => 'detalle-planes.consolidado-minutos'], function(){
        Route::get('/', [\AMovil\Reports\DetallePlanes\ConsolidadoMinutos\Controllers\ConsolidadoMinutosController::class, 'view']);
        Route::post('export', [\AMovil\Reports\DetallePlanes\ConsolidadoMinutos\Controllers\ConsolidadoMinutosController::class, 'export']);
    });
    
    Route::group(['prefix' => 'olt-cmts', 'trac_name' => 'olt-cmts'], function(){
        Route::get('/', [\AMovil\Reports\OltCmts\Controllers\OltCmtsController::class, 'view']);
        Route::get('find-values', [\AMovil\Reports\OltCmts\Controllers\OltCmtsController::class, 'findValues']);
        Route::post('export', [\AMovil\Reports\OltCmts\Controllers\OltCmtsController::class, 'export']);
    });

    Route::group(['prefix' => 'reporte-esim', 'trac_name' => 'reporte-esim'], function(){
        Route::get('/', [\AMovil\Reports\ReporteEsim\Controllers\ReporteEsimController::class, 'view']);
        Route::post('export', [\AMovil\Reports\ReporteEsim\Controllers\ReporteEsimController::class, 'export']);
    });

    Route::group(['prefix' => 'mantenimiento-celdas', 'trac_name' => 'mantenimiento-celdas'], function(){
        Route::get('/', [\AMovil\Reports\MantenimientoCeldas\Mantenimiento\Controllers\MantenimientoCeldaController::class, 'view']);
        Route::post('process', [\AMovil\Reports\MantenimientoCeldas\Mantenimiento\Controllers\MantenimientoCeldaController::class, 'process']);
        // Route::post('export', [\AMovil\Reports\MantenimientoCeldas\Mantenimiento\Controllers\MantenimientoCeldaController::class, 'export']);
    });
    Route::group(['prefix' => 'mantenimiento-celdas/tickets', 'trac_name' => 'mantenimiento-celdas.tickets'], function(){
        Route::get('/', [\AMovil\Reports\MantenimientoCeldas\TicketReports\Controllers\TicketReportController::class, 'view']);
        Route::post('search', [\AMovil\Reports\MantenimientoCeldas\TicketReports\Controllers\TicketReportController::class, 'search']);
    });
    Route::group(['prefix' => 'mantenimiento-celdas/eliminar-ticket', 'trac_name' => 'mantenimiento-celdas.eliminar-ticket'], function(){
        Route::get('/', [\AMovil\Reports\MantenimientoCeldas\TicketReports\Controllers\TicketReportController::class, 'deleteView']);
        Route::post('/', [\AMovil\Reports\MantenimientoCeldas\TicketReports\Controllers\TicketReportController::class, 'delete']);
    });
    Route::group(['prefix' => 'mantenimiento-celdas/reportes', 'trac_name' => 'mantenimiento-celdas.reportes'], function(){
        Route::get('/', [\AMovil\Reports\MantenimientoCeldas\Mantenimiento\Controllers\MantenimientoCeldaController::class, 'reportsView']);
        Route::post('input', [\AMovil\Reports\MantenimientoCeldas\Mantenimiento\Controllers\MantenimientoCeldaController::class, 'findInput']);
        Route::post('export', [\AMovil\Reports\MantenimientoCeldas\Mantenimiento\Controllers\MantenimientoCeldaController::class, 'export']);
    });
    Route::group(['prefix' => 'lista-excepciones', 'trac_name' => 'lista-excepciones'], function(){
        Route::get('/', [\AMovil\Reports\ListaExcepciones\Controllers\ListaExcepcionesController::class, 'view']);
        Route::get('search', [\AMovil\Reports\ListaExcepciones\Controllers\ListaExcepcionesController::class, 'search']);
        Route::post('export', [\AMovil\Reports\ListaExcepciones\Controllers\ListaExcepcionesController::class, 'export']);
    });
    Route::group(['prefix' => 'lista-excepciones-eliminar', 'trac_name' => 'lista-excepciones-eliminar'], function(){
        Route::get('/', [\AMovil\Reports\ListaExcepcionesEliminar\Controllers\ListaExcepcionesEliminarController::class, 'view']);
        Route::get('search', [\AMovil\Reports\ListaExcepcionesEliminar\Controllers\ListaExcepcionesEliminarController::class, 'search']);
        Route::post('export', [\AMovil\Reports\ListaExcepcionesEliminar\Controllers\ListaExcepcionesEliminarController::class, 'export']);
    });
    Route::group(['prefix' => 'lista-excepciones-masivo', 'trac_name' => 'lista-excepciones-masivo'], function(){
        Route::get('/', [\AMovil\Reports\ListaExcepcionesMasivo\Controllers\ListaExcepcionesMasivoController::class, 'view']);
        Route::post('/import', [\AMovil\Reports\ListaExcepcionesMasivo\Controllers\ListaExcepcionesMasivoController::class, 'import']);
        Route::get('/search', [\AMovil\Reports\ListaExcepcionesMasivo\Controllers\ListaExcepcionesMasivoController::class, 'get']);
        Route::get('/{filename}/download', [\AMovil\Reports\ListaExcepcionesMasivo\Controllers\ListaExcepcionesMasivoController::class, 'downloadFile']);
    });
    Route::group(['prefix' => 'lista-excepciones-masivo/logs', 'trac_name' => 'lista-excepciones-masivo.logs'], function(){
        Route::get('/', [\AMovil\Reports\ListaExcepcionesMasivo\Controllers\ListaExcepcionesMasivoController::class, 'viewLog']);
        Route::get('/search', [\AMovil\Reports\ListaExcepcionesMasivo\Controllers\ListaExcepcionesMasivoController::class, 'get']);
        Route::get('/{id}/download-eir', [\AMovil\Reports\ListaExcepcionesMasivo\Controllers\ListaExcepcionesMasivoController::class, 'downloadEirResponse']);
    });
    Route::group(['prefix' => 'lista-excepciones-art25', 'trac_name' => 'lista-excepciones-art25'], function(){
        Route::get('/', [\AMovil\Reports\ListaExcepcionesArt25\Controllers\ListaExcepcionesArt25Controller::class, 'view']);
        Route::get('search', [\AMovil\Reports\ListaExcepcionesArt25\Controllers\ListaExcepcionesArt25Controller::class, 'search']);
        Route::post('export', [\AMovil\Reports\ListaExcepcionesArt25\Controllers\ListaExcepcionesArt25Controller::class, 'export']);
    });

    Route::group(['prefix' => 'clientes-planos', 'trac_name' => 'clientes-planos'], function(){
        Route::get('/', [\AMovil\Reports\ClientesPlanos\Controllers\ClientesPlanosController::class, 'view']);
        Route::get('find-values', [\AMovil\Reports\ClientesPlanos\Controllers\ClientesPlanosController::class, 'findValues']);
        Route::post('export', [\AMovil\Reports\ClientesPlanos\Controllers\ClientesPlanosController::class, 'export']);
        Route::get('/descarga-reportes', [\AMovil\Reports\ClientesPlanos\Controllers\ClientesPlanosController::class, 'viewReports']);
        Route::get('/descarga-reportes/search', [\AMovil\Reports\ClientesPlanos\Controllers\ClientesPlanosController::class, 'search']);
        Route::get('/descarga-reportes/{file}/download', [\AMovil\Reports\ClientesPlanos\Controllers\ClientesPlanosController::class, 'download']);
    });
    
    Route::group(['prefix' => 'cargos-fijos-servicios-activos/reporte', 'trac_name' => 'cargos-fijos-servicios-activos.reporte'], function(){
        Route::get('/', [\AMovil\Reports\CargosFijosServiciosActivos\Controllers\ReporteController::class, 'reporte']);
        Route::post('export', [\AMovil\Reports\CargosFijosServiciosActivos\Controllers\ReporteController::class, 'descargaReporte']);
    });

    Route::group(['prefix' => 'cargos-fijos-servicios-activos/reportes', 'trac_name' => 'cargos-fijos-servicios-activos.search'], function(){
        Route::get('/', [\AMovil\Reports\CargosFijosServiciosActivos\Controllers\LogReporteTempController::class, 'view']);
        Route::get('search', [\AMovil\Reports\CargosFijosServiciosActivos\Controllers\LogReporteTempController::class, 'search']);
        Route::get('{file}/download', [\AMovil\Reports\CargosFijosServiciosActivos\Controllers\LogReporteTempController::class, 'download']);
    });

    Route::group(['prefix' => 'clientes-mac-sn', 'trac_name' => 'clientes-mac-sn'], function(){
        Route::get('/', [\AMovil\Reports\ClientesMacSn\Controllers\ClientesMacSnController::class, 'view']);
        Route::post('export', [\AMovil\Reports\ClientesMacSn\Controllers\ClientesMacSnController::class, 'export']);
    });

    Route::group(['prefix' => 'base-tipificaciones', 'trac_name' => 'base-tipificaciones.index'], function(){
        Route::get('/', [\AMovil\Reports\BaseTipificaciones\Controllers\BaseTipificacionesController::class, 'view']);
        Route::post('/import', [\AMovil\Reports\BaseTipificaciones\Controllers\BaseTipificacionesController::class, 'import']);
    });
    
    Route::group(['prefix' => 'reporte-lineas-enrutadas', 'trac_name' => 'reporte-lineas-enrutadas'], function(){
        Route::get('/', [\AMovil\Reports\ReporteLineasEnrutadas\Controllers\ReporteLineasEnrutadasController::class, 'view']);
        Route::post('export', [\AMovil\Reports\ReporteLineasEnrutadas\Controllers\ReporteLineasEnrutadasController::class, 'export']);
    });

    Route::group(['prefix' => 'retenciones', 'trac_name' => 'retenciones.index'], function(){
        Route::get('/', [\AMovil\Reports\Retenciones\Controllers\ListadoController::class, 'view']);
        Route::post('/store', [\AMovil\Reports\Retenciones\Controllers\ListadoController::class, 'store'])->name("retenciones-store");
        Route::post('/store-rutas', [\AMovil\Reports\Retenciones\Controllers\ListadoController::class, 'rutas'])->name("retenciones-store-rutas");
        Route::get('/historico-combinaciones', [\AMovil\Reports\Retenciones\Controllers\ListadoController::class, 'historicoCombinaciones'])->name("retenciones-historico-combinaciones");
        Route::get('/historico-rutas', [\AMovil\Reports\Retenciones\Controllers\ListadoController::class, 'historicoRutas'])->name("retenciones-historico-rutas");
        Route::get('/historico/combinaciones', [\AMovil\Reports\Retenciones\Controllers\ListadoController::class, 'getCombinaciones']);
        Route::get('/historico/rutas', [\AMovil\Reports\Retenciones\Controllers\ListadoController::class, 'getRutas']);
        Route::get('/historico/descargar-plantilla/{filename}', function ($filename) {
            $file = public_path('resources/' . $filename);
            if (file_exists($file)) {
                return Response::download($file);
            } else {
                abort(404); // Error si el archivo no existe
            }
        });
    });

});