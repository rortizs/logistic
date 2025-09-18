<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CamionController;
use App\Http\Controllers\PilotoController;
use App\Http\Controllers\RutaController;
use App\Http\Controllers\ViajeController;
use App\Http\Controllers\MantenimientoController;

Route::get('/', function () {
    return view('welcome');
});

// Dashboard route (Filament provides authentication)
Route::get('/home', function () {
    return redirect('/admin');
})->name('home');

// Resource routes for main entities
Route::resource('camiones', CamionController::class);
Route::resource('pilotos', PilotoController::class);
Route::resource('rutas', RutaController::class);
Route::resource('viajes', ViajeController::class);
Route::resource('mantenimientos', MantenimientoController::class);

// Additional Camion routes with stored procedures
Route::prefix('camiones')->name('camiones.')->group(function () {
    Route::get('/{camion}/verificar-mantenimiento-completo', [CamionController::class, 'verificarMantenimientoCompleto'])
        ->name('verificar-mantenimiento-completo');
    Route::post('/{camion}/actualizar-kilometraje', [CamionController::class, 'actualizarKilometraje'])
        ->name('actualizar-kilometraje');
    Route::post('/{camion}/cambiar-estado', [CamionController::class, 'cambiarEstado'])
        ->name('cambiar-estado');
    Route::get('/verificar-mantenimiento', [CamionController::class, 'verificarMantenimiento'])
        ->name('verificar-mantenimiento');
    Route::get('/disponibles', [CamionController::class, 'disponibles'])
        ->name('disponibles');
    Route::get('/estadisticas', [CamionController::class, 'estadisticas'])
        ->name('estadisticas');
});

// Additional Viaje routes
Route::prefix('viajes')->name('viajes.')->group(function () {
    Route::post('/{viaje}/iniciar', [ViajeController::class, 'iniciarViaje'])->name('iniciar');
    Route::post('/{viaje}/completar', [ViajeController::class, 'completarViaje'])->name('completar');
    Route::post('/{viaje}/cancelar', [ViajeController::class, 'cancelarViaje'])->name('cancelar');
    Route::post('/{viaje}/reprogramar', [ViajeController::class, 'reprogramarViaje'])->name('reprogramar');
    Route::get('/estadisticas', [ViajeController::class, 'estadisticas'])->name('estadisticas');

    // New stored procedure routes
    Route::post('/generar-reporte', [ViajeController::class, 'generarReporte'])->name('generar-reporte');
    Route::post('/exportar-reporte', [ViajeController::class, 'exportarReporte'])->name('exportar-reporte');
});

// Additional Mantenimiento routes
Route::prefix('mantenimientos')->name('mantenimientos.')->group(function () {
    Route::post('/{mantenimiento}/completar', [MantenimientoController::class, 'completar'])
        ->name('completar');
    Route::post('/{mantenimiento}/cancelar', [MantenimientoController::class, 'cancelar'])
        ->name('cancelar');
    Route::get('/verificar-vencidos', [MantenimientoController::class, 'verificarVencidos'])
        ->name('verificar-vencidos');
    Route::get('/estadisticas', [MantenimientoController::class, 'estadisticas'])
        ->name('estadisticas');
    Route::get('/calendario', [MantenimientoController::class, 'calendario'])
        ->name('calendario');

    // New stored procedure route
    Route::post('/verificar-mantenimiento-camion', [MantenimientoController::class, 'verificarMantenimientoCamion'])
        ->name('verificar-mantenimiento-camion');
});

// Additional Piloto routes
Route::prefix('pilotos')->name('pilotos.')->group(function () {
    Route::post('/{piloto}/cambiar-estado', [PilotoController::class, 'cambiarEstado'])
        ->name('cambiar-estado');
    Route::get('/disponibles', [PilotoController::class, 'disponibles'])
        ->name('disponibles');
    Route::get('/estadisticas', [PilotoController::class, 'estadisticas'])
        ->name('estadisticas');
});

// Additional Ruta routes
Route::prefix('rutas')->name('rutas.')->group(function () {
    Route::post('/{ruta}/cambiar-estado', [RutaController::class, 'cambiarEstado'])
        ->name('cambiar-estado');
    Route::get('/activas', [RutaController::class, 'activas'])
        ->name('activas');
    Route::get('/estadisticas', [RutaController::class, 'estadisticas'])
        ->name('estadisticas');
});
