<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArticuloController;
use App\Http\Controllers\VentasController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\LocalidadController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\VarianteController;
use App\Http\Controllers\AtributoDefinidoController;
use App\Http\Controllers\ValorAtributoController;
use App\Http\Middleware\TokenAuthMiddleware;
use Illuminate\Http\Request;

/**
 * ---------------------------
 * 🔓 Rutas públicas
 * ---------------------------
 */
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);

// Opcional para debug
Route::get('/debug-error', fn () => response()->json(['debug' => 'OK']));





/**
 * ---------------------------
 * 🔐 Rutas protegidas con auth:sanctum
 * ---------------------------
 */
Route::middleware('token-auth')->group(function () {
    
    Route::get('/check-auth', [LoginController::class, 'checkAuth']);

    // Atributos definidos
    Route::get   ('/atributos',                        [AtributoDefinidoController::class, 'index']);
    Route::post  ('/atributos',                        [AtributoDefinidoController::class, 'store']);
    Route::put   ('/atributos/{id}',                   [AtributoDefinidoController::class, 'update']);
    Route::delete('/atributos/{id}',                   [AtributoDefinidoController::class, 'destroy']);

    // Valores de atributo
    Route::get   ('/atributos/{atributoId}/valores',   [ValorAtributoController::class, 'index']);
    Route::post  ('/atributos/{atributoId}/valores',   [ValorAtributoController::class, 'store']);
    Route::put   ('/valores/{id}',                     [ValorAtributoController::class, 'update']);
    Route::delete('/valores/{id}',                     [ValorAtributoController::class, 'destroy']);
        
    // Variante
    Route::put('/variantes/{id}', [VarianteController::class, 'update']);
    Route::delete('/variantes/{id}', [VarianteController::class, 'destroy']);
    Route::post('/variantes/{id}/ajustar-stock', [VarianteController::class, 'ajustarStock']);

    // Artículos
    Route::get   ('/articulos',                       [ArticuloController::class,'index']);
    Route::get   ('/articulos/ultima-actualizacion',  [ArticuloController::class,'ultimaActualizacionArticulos']);
    Route::get   ('/articulos/actualizados-desde',    [ArticuloController::class,'articulosActualizadosDesde']);
    Route::post  ('/articulos',                       [ArticuloController::class,'store']);
    Route::get   ('/articulos/{id}',                  [ArticuloController::class,'show']);
    Route::put   ('/articulos/{id}',                  [ArticuloController::class,'update']);
    Route::delete('/articulos/{id}',                  [ArticuloController::class,'destroy']);
    
    // Ventas
    Route::post  ('/ventas',                         [VentasController::class, 'registrarVenta']);
    Route::get   ('/ventas/listar',                  [VentasController::class, 'obtenerVentas']);
    Route::put   ('/ventas/{id}',                    [VentasController::class, 'update']);
    Route::delete('/ventas/{id}',                    [VentasController::class, 'destroy']);
    Route::post  ('/ventas/cambiar-variante',        [VentasController::class, 'cambiarBombacha']);
    Route::get   ('/facturaciones/ultima',           [VentasController::class, 'obtenerUltimaFacturacion']);
    Route::post  ('/facturaciones/guardar',          [VentasController::class, 'guardarFacturaciones']);
    Route::get   ('/ventas/ultima-actualizacion',    [VentasController::class, 'ultimaActualizacionVentas']);
    Route::get   ('/ventas/actualizados-desde',      [VentasController::class, 'ventasActualizadasDesde']);

    // Calendario
    Route::get('/comprascalendario/listar', [CalendarioController::class, 'index']);
    Route::post('/comprascalendario', [CalendarioController::class, 'store']);
    Route::put('/comprascalendario/{id}', [CalendarioController::class, 'update']);
    Route::delete('/comprascalendario/{id}', [CalendarioController::class, 'destroy']); 
    Route::get('/comprascalendario/ultima-actualizacion', [CalendarioController::class, 'ultimaActualizacionVentas']);
    Route::get('/calendario/actualizados-desde', [CalendarioController::class, 'calendarioActualizadosDesde']);
    
    // Clientes
    Route::get('/clientes/listar', [ClienteController::class, 'index']);
    Route::post('/cliente', [ClienteController::class, 'store']);
    Route::put('/cliente/{id}', [ClienteController::class, 'update']);
    Route::delete('/cliente/{id}', [ClienteController::class, 'destroy']);
    Route::get('/clientes/ultima-actualizacion', [ClienteController::class, 'ultimaActualizacionClientes']);
    Route::get('/clientes/actualizados-desde', [ClienteController::class, 'clientesActualizadosDesde']);
    
    // Localidades
    Route::get('/localidades', [LocalidadController::class, 'index']);
    Route::post('/localidad', [LocalidadController::class, 'store']);
    Route::put('/localidad/{id}', [LocalidadController::class, 'update']);
    Route::delete('/localidad/{id}', [LocalidadController::class, 'destroy']);
    Route::get('/localidad/ultima-actualizacion', [LocalidadController::class, 'ultimaActualizacionLocalidades']);
    Route::get('/localidades/actualizados-desde', [LocalidadController::class, 'localidadesActualizadasDesde']);
    
    // Google Drive
    Route::get('/google/redirect', [GoogleDriveController::class, 'redirectToGoogle'])->name('google.redirect');
    Route::get('/auth/google', [GoogleDriveController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/google/callback', [GoogleDriveController::class, 'handleGoogleCallback']);
    Route::get('/upload-to-drive', [GoogleDriveController::class, 'uploadToDrive'])->name('drive.upload');
});