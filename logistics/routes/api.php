<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CamionController;
use App\Http\Controllers\PilotoController;
use App\Http\Controllers\RutaController;
use App\Http\Controllers\ViajeController;
use App\Http\Controllers\MantenimientoController;

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

// API routes for logistics system
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    // Camiones API routes
    Route::prefix('camiones')->group(function () {
        Route::get('/', [CamionController::class, 'index']);
        Route::post('/', [CamionController::class, 'store']);
        Route::get('/{camion}', [CamionController::class, 'show']);
        Route::put('/{camion}', [CamionController::class, 'update']);
        Route::delete('/{camion}', [CamionController::class, 'destroy']);

        // Stored procedure endpoints
        Route::get('/{camion}/verificar-mantenimiento-completo', [CamionController::class, 'verificarMantenimientoCompleto']);
        Route::post('/{camion}/actualizar-kilometraje', [CamionController::class, 'actualizarKilometraje']);
        Route::post('/{camion}/cambiar-estado', [CamionController::class, 'cambiarEstado']);
        Route::get('/verificar-mantenimiento', [CamionController::class, 'verificarMantenimiento']);
        Route::get('/disponibles', [CamionController::class, 'disponibles']);
        Route::get('/estadisticas', [CamionController::class, 'estadisticas']);
    });

    // Viajes API routes
    Route::prefix('viajes')->group(function () {
        Route::get('/', [ViajeController::class, 'index']);
        Route::post('/', [ViajeController::class, 'store']);
        Route::get('/{viaje}', [ViajeController::class, 'show']);
        Route::put('/{viaje}', [ViajeController::class, 'update']);
        Route::delete('/{viaje}', [ViajeController::class, 'destroy']);

        // Trip lifecycle endpoints
        Route::post('/{viaje}/iniciar', [ViajeController::class, 'iniciarViaje']);
        Route::post('/{viaje}/completar', [ViajeController::class, 'completarViaje']);
        Route::post('/{viaje}/cancelar', [ViajeController::class, 'cancelarViaje']);
        Route::post('/{viaje}/reprogramar', [ViajeController::class, 'reprogramarViaje']);
        Route::get('/estadisticas', [ViajeController::class, 'estadisticas']);

        // Stored procedure endpoints
        Route::post('/generar-reporte', [ViajeController::class, 'generarReporte']);
        Route::post('/exportar-reporte', [ViajeController::class, 'exportarReporte']);
    });

    // Mantenimientos API routes
    Route::prefix('mantenimientos')->group(function () {
        Route::get('/', [MantenimientoController::class, 'index']);
        Route::post('/', [MantenimientoController::class, 'store']);
        Route::get('/{mantenimiento}', [MantenimientoController::class, 'show']);
        Route::put('/{mantenimiento}', [MantenimientoController::class, 'update']);
        Route::delete('/{mantenimiento}', [MantenimientoController::class, 'destroy']);

        // Maintenance lifecycle endpoints
        Route::post('/{mantenimiento}/completar', [MantenimientoController::class, 'completar']);
        Route::post('/{mantenimiento}/cancelar', [MantenimientoController::class, 'cancelar']);
        Route::get('/verificar-vencidos', [MantenimientoController::class, 'verificarVencidos']);
        Route::get('/estadisticas', [MantenimientoController::class, 'estadisticas']);
        Route::get('/calendario', [MantenimientoController::class, 'calendario']);

        // Stored procedure endpoint
        Route::post('/verificar-mantenimiento-camion', [MantenimientoController::class, 'verificarMantenimientoCamion']);
    });

    // Pilotos API routes
    Route::prefix('pilotos')->group(function () {
        Route::get('/', [PilotoController::class, 'index']);
        Route::post('/', [PilotoController::class, 'store']);
        Route::get('/{piloto}', [PilotoController::class, 'show']);
        Route::put('/{piloto}', [PilotoController::class, 'update']);
        Route::delete('/{piloto}', [PilotoController::class, 'destroy']);

        Route::post('/{piloto}/cambiar-estado', [PilotoController::class, 'cambiarEstado']);
        Route::get('/disponibles', [PilotoController::class, 'disponibles']);
        Route::get('/estadisticas', [PilotoController::class, 'estadisticas']);
    });

    // Rutas API routes
    Route::prefix('rutas')->group(function () {
        Route::get('/', [RutaController::class, 'index']);
        Route::post('/', [RutaController::class, 'store']);
        Route::get('/{ruta}', [RutaController::class, 'show']);
        Route::put('/{ruta}', [RutaController::class, 'update']);
        Route::delete('/{ruta}', [RutaController::class, 'destroy']);

        Route::post('/{ruta}/cambiar-estado', [RutaController::class, 'cambiarEstado']);
        Route::get('/activas', [RutaController::class, 'activas']);
        Route::get('/estadisticas', [RutaController::class, 'estadisticas']);
    });
});

// Public API routes (no authentication required)
Route::prefix('public/v1')->group(function () {
    // Public endpoints for basic information (if needed)
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'Logistics Management API',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString()
        ]);
    });

    Route::get('/info', function () {
        return response()->json([
            'service' => 'Logistics Management System',
            'version' => '1.0.0',
            'description' => 'Complete logistics management with fleet tracking, maintenance, and trip management',
            'features' => [
                'Fleet Management',
                'Driver Management',
                'Route Management',
                'Trip Tracking',
                'Maintenance Scheduling',
                'Comprehensive Reporting via Stored Procedures'
            ],
            'stored_procedures' => [
                'ActualizarKilometrajeCamion' => 'Updates truck mileage after trip completion',
                'VerificarMantenimientoPendiente' => 'Checks comprehensive maintenance status for trucks',
                'GenerarReporteViajes' => 'Generates detailed trip reports with statistics'
            ]
        ]);
    });
});