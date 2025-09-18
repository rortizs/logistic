<?php

namespace App\Http\Controllers;

use App\Http\Requests\ViajeRequest;
use App\Models\Viaje;
use App\Models\Camion;
use App\Models\Piloto;
use App\Models\Ruta;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

/**
 * Class ViajeController
 *
 * Handles all trip-related operations including CRUD operations,
 * trip lifecycle management (start, complete, cancel, reschedule),
 * and integration with stored procedures for business logic.
 *
 * @package App\Http\Controllers
 */
class ViajeController extends Controller
{
    /**
     * ViajeController constructor.
     * Apply middleware for authentication and authorization.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('web');
    }

    /**
     * Display a paginated listing of trips with filtering and search capabilities.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Viaje::with(['camion', 'piloto', 'ruta']);

            // Apply filters
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('camion_id')) {
                $query->where('camion_id', $request->camion_id);
            }

            if ($request->filled('piloto_id')) {
                $query->where('piloto_id', $request->piloto_id);
            }

            if ($request->filled('ruta_id')) {
                $query->where('ruta_id', $request->ruta_id);
            }

            if ($request->filled('fecha_inicio')) {
                $query->whereDate('fecha_inicio', '>=', $request->fecha_inicio);
            }

            if ($request->filled('fecha_fin')) {
                $query->whereDate('fecha_inicio', '<=', $request->fecha_fin);
            }

            // Search functionality
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereHas('camion', function ($camionQuery) use ($searchTerm) {
                        $camionQuery->where('placa', 'like', "%{$searchTerm}%")
                            ->orWhere('marca', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('piloto', function ($pilotoQuery) use ($searchTerm) {
                        $pilotoQuery->where('nombre', 'like', "%{$searchTerm}%")
                            ->orWhere('apellido', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('ruta', function ($rutaQuery) use ($searchTerm) {
                        $rutaQuery->where('origen', 'like', "%{$searchTerm}%")
                            ->orWhere('destino', 'like', "%{$searchTerm}%");
                    });
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'fecha_inicio');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $viajes = $query->paginate($perPage)->appends($request->query());

            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $viajes,
                    'message' => 'Viajes retrieved successfully'
                ]);
            }

            // Get filter data for view
            $camiones = Camion::activos()->orderBy('placa')->get();
            $pilotos = Piloto::activos()->orderBy('nombre')->get();
            $rutas = Ruta::activas()->orderBy('origen')->get();

            return view('viajes.index', compact('viajes', 'camiones', 'pilotos', 'rutas'));

        } catch (Exception $e) {
            Log::error('Error in ViajeController@index: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error retrieving trips'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return redirect()->back()->with('error', 'Error al cargar los viajes');
        }
    }

    /**
     * Show the form for creating a new trip.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        try {
            $camiones = Camion::activos()->orderBy('placa')->get();
            $pilotos = Piloto::disponibles()->orderBy('nombre')->get();
            $rutas = Ruta::activas()->orderBy('origen')->get();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'camiones' => $camiones,
                        'pilotos' => $pilotos,
                        'rutas' => $rutas
                    ]
                ]);
            }

            return view('viajes.create', compact('camiones', 'pilotos', 'rutas'));

        } catch (Exception $e) {
            Log::error('Error in ViajeController@create: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error loading create form data'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return redirect()->route('viajes.index')->with('error', 'Error al cargar el formulario');
        }
    }

    /**
     * Store a newly created trip in storage.
     *
     * @param ViajeRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(ViajeRequest $request)
    {
        DB::beginTransaction();
        
        try {
            // Validate resources availability
            $camion = Camion::findOrFail($request->camion_id);
            $piloto = Piloto::findOrFail($request->piloto_id);
            $ruta = Ruta::findOrFail($request->ruta_id);

            if (!$camion->estaDisponible()) {
                throw new Exception('El camión no está disponible');
            }

            if (!$piloto->esta_disponible) {
                throw new Exception('El piloto no está disponible');
            }

            if ($ruta->estado !== Ruta::ESTADO_ACTIVA) {
                throw new Exception('La ruta no está activa');
            }

            // Set initial mileage from camion's current mileage
            $data = $request->validated();
            $data['kilometraje_inicial'] = $camion->kilometraje_actual;
            $data['estado'] = Viaje::ESTADO_PROGRAMADO;

            $viaje = Viaje::create($data);

            DB::commit();
            
            Log::info('Trip created successfully', ['viaje_id' => $viaje->id, 'user_id' => Auth::id()]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $viaje->load(['camion', 'piloto', 'ruta']),
                    'message' => 'Viaje creado exitosamente'
                ], Response::HTTP_CREATED);
            }

            return redirect()->route('viajes.show', $viaje)
                ->with('success', 'Viaje creado exitosamente');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in ViajeController@store: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified trip.
     *
     * @param Viaje $viaje
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function show(Viaje $viaje, Request $request)
    {
        try {
            $viaje->load(['camion', 'piloto', 'ruta']);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $viaje
                ]);
            }

            return view('viajes.show', compact('viaje'));

        } catch (Exception $e) {
            Log::error('Error in ViajeController@show: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Trip not found'
                ], Response::HTTP_NOT_FOUND);
            }

            return redirect()->route('viajes.index')->with('error', 'Viaje no encontrado');
        }
    }

    /**
     * Show the form for editing the specified trip.
     *
     * @param Viaje $viaje
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function edit(Viaje $viaje, Request $request)
    {
        try {
            // Only allow editing if trip is in PROGRAMADO status
            if ($viaje->estado !== Viaje::ESTADO_PROGRAMADO) {
                throw new Exception('Solo se pueden editar viajes en estado Programado');
            }

            $camiones = Camion::activos()->orderBy('placa')->get();
            $pilotos = Piloto::disponibles()->orderBy('nombre')->get();
            $rutas = Ruta::activas()->orderBy('origen')->get();

            // Include current assigned resources even if they're not available
            if (!$pilotos->contains('id', $viaje->piloto_id)) {
                $pilotos->push($viaje->piloto);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'viaje' => $viaje->load(['camion', 'piloto', 'ruta']),
                        'camiones' => $camiones,
                        'pilotos' => $pilotos,
                        'rutas' => $rutas
                    ]
                ]);
            }

            return view('viajes.edit', compact('viaje', 'camiones', 'pilotos', 'rutas'));

        } catch (Exception $e) {
            Log::error('Error in ViajeController@edit: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update the specified trip in storage.
     *
     * @param ViajeRequest $request
     * @param Viaje $viaje
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function update(ViajeRequest $request, Viaje $viaje)
    {
        DB::beginTransaction();
        
        try {
            // Only allow updating if trip is in PROGRAMADO status
            if ($viaje->estado !== Viaje::ESTADO_PROGRAMADO) {
                throw new Exception('Solo se pueden editar viajes en estado Programado');
            }

            // Validate resources availability (only if they're changing)
            if ($viaje->camion_id !== $request->camion_id) {
                $camion = Camion::findOrFail($request->camion_id);
                if (!$camion->estaDisponible()) {
                    throw new Exception('El camión no está disponible');
                }
            }

            if ($viaje->piloto_id !== $request->piloto_id) {
                $piloto = Piloto::findOrFail($request->piloto_id);
                if (!$piloto->esta_disponible) {
                    throw new Exception('El piloto no está disponible');
                }
            }

            if ($viaje->ruta_id !== $request->ruta_id) {
                $ruta = Ruta::findOrFail($request->ruta_id);
                if ($ruta->estado !== Ruta::ESTADO_ACTIVA) {
                    throw new Exception('La ruta no está activa');
                }
            }

            $viaje->update($request->validated());

            DB::commit();
            
            Log::info('Trip updated successfully', ['viaje_id' => $viaje->id, 'user_id' => Auth::id()]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $viaje->load(['camion', 'piloto', 'ruta']),
                    'message' => 'Viaje actualizado exitosamente'
                ]);
            }

            return redirect()->route('viajes.show', $viaje)
                ->with('success', 'Viaje actualizado exitosamente');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in ViajeController@update: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified trip from storage.
     *
     * @param Viaje $viaje
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroy(Viaje $viaje, Request $request)
    {
        DB::beginTransaction();
        
        try {
            // Only allow deletion if trip is in PROGRAMADO or CANCELADO status
            if (!in_array($viaje->estado, [Viaje::ESTADO_PROGRAMADO, Viaje::ESTADO_CANCELADO])) {
                throw new Exception('Solo se pueden eliminar viajes en estado Programado o Cancelado');
            }

            $viajeId = $viaje->id;
            $viaje->delete();

            DB::commit();
            
            Log::info('Trip deleted successfully', ['viaje_id' => $viajeId, 'user_id' => Auth::id()]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Viaje eliminado exitosamente'
                ]);
            }

            return redirect()->route('viajes.index')
                ->with('success', 'Viaje eliminado exitosamente');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in ViajeController@destroy: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Start a trip - change status from PROGRAMADO to EN_CURSO
     *
     * @param Viaje $viaje
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function iniciarViaje(Viaje $viaje, Request $request)
    {
        DB::beginTransaction();
        
        try {
            if ($viaje->estado !== Viaje::ESTADO_PROGRAMADO) {
                throw new Exception('Solo se pueden iniciar viajes en estado Programado');
            }

            // Validate that resources are still available
            if (!$viaje->recursosDisponibles()) {
                throw new Exception('Los recursos asignados no están disponibles');
            }

            // Update mileage if provided
            if ($request->filled('kilometraje_inicial')) {
                $viaje->kilometraje_inicial = $request->kilometraje_inicial;
            }

            $success = $viaje->iniciar();
            
            if (!$success) {
                throw new Exception('No se pudo iniciar el viaje');
            }

            DB::commit();
            
            Log::info('Trip started successfully', ['viaje_id' => $viaje->id, 'user_id' => Auth::id()]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $viaje->load(['camion', 'piloto', 'ruta']),
                    'message' => 'Viaje iniciado exitosamente'
                ]);
            }

            return redirect()->back()->with('success', 'Viaje iniciado exitosamente');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in ViajeController@iniciarViaje: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Complete a trip - enhanced version of the original completarViaje method
     *
     * @param Viaje $viaje
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function completarViaje(Viaje $viaje, Request $request)
    {
        // Validate request
        $request->validate([
            'kilometraje_final' => 'required|numeric|min:' . ($viaje->kilometraje_inicial ?? 0),
            'observaciones' => 'nullable|string|max:1000'
        ]);

        DB::beginTransaction();
        
        try {
            if ($viaje->estado !== Viaje::ESTADO_EN_CURSO) {
                throw new Exception('Solo se pueden completar viajes en curso');
            }

            $kilometrajeFinal = $request->kilometraje_final;
            
            // Complete the trip using model method
            $success = $viaje->completar($kilometrajeFinal);
            
            if (!$success) {
                throw new Exception('No se pudo completar el viaje');
            }

            // Calculate kilometers traveled
            $kilometrosRecorridos = $kilometrajeFinal - $viaje->kilometraje_inicial;

            // Call stored procedure to update camion mileage
            if ($kilometrosRecorridos > 0) {
                DB::statement('CALL ActualizarKilometrajeCamion(?, ?)', [
                    $viaje->camion_id,
                    $kilometrosRecorridos
                ]);
            }

            // Add observations if provided
            if ($request->filled('observaciones')) {
                // You might want to create an observations table or add a field to viajes table
                // For now, we'll log it
                Log::info('Trip completed with observations', [
                    'viaje_id' => $viaje->id,
                    'observaciones' => $request->observaciones,
                    'user_id' => Auth::id()
                ]);
            }

            DB::commit();
            
            Log::info('Trip completed successfully', [
                'viaje_id' => $viaje->id, 
                'kilometros_recorridos' => $kilometrosRecorridos,
                'user_id' => Auth::id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $viaje->load(['camion', 'piloto', 'ruta']),
                    'message' => '¡Viaje completado y kilometraje actualizado!'
                ]);
            }

            return redirect()->back()->with('success', '¡Viaje completado y kilometraje actualizado!');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in ViajeController@completarViaje: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel a trip
     *
     * @param Viaje $viaje
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function cancelarViaje(Viaje $viaje, Request $request)
    {
        // Validate request
        $request->validate([
            'motivo_cancelacion' => 'required|string|max:500'
        ]);

        DB::beginTransaction();
        
        try {
            if ($viaje->estado === Viaje::ESTADO_COMPLETADO) {
                throw new Exception('No se pueden cancelar viajes completados');
            }

            $success = $viaje->cancelar();
            
            if (!$success) {
                throw new Exception('No se pudo cancelar el viaje');
            }

            DB::commit();
            
            Log::info('Trip cancelled successfully', [
                'viaje_id' => $viaje->id,
                'motivo' => $request->motivo_cancelacion,
                'user_id' => Auth::id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $viaje->load(['camion', 'piloto', 'ruta']),
                    'message' => 'Viaje cancelado exitosamente'
                ]);
            }

            return redirect()->back()->with('success', 'Viaje cancelado exitosamente');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in ViajeController@cancelarViaje: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reschedule a trip to a new date
     *
     * @param Viaje $viaje
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function reprogramarViaje(Viaje $viaje, Request $request)
    {
        // Validate request
        $request->validate([
            'nueva_fecha_inicio' => 'required|date|after:now',
            'motivo_reprogramacion' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();
        
        try {
            if ($viaje->estado !== Viaje::ESTADO_PROGRAMADO) {
                throw new Exception('Solo se pueden reprogramar viajes en estado Programado');
            }

            $viaje->fecha_inicio = $request->nueva_fecha_inicio;
            $viaje->save();

            DB::commit();
            
            Log::info('Trip rescheduled successfully', [
                'viaje_id' => $viaje->id,
                'nueva_fecha' => $request->nueva_fecha_inicio,
                'motivo' => $request->motivo_reprogramacion,
                'user_id' => Auth::id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $viaje->load(['camion', 'piloto', 'ruta']),
                    'message' => 'Viaje reprogramado exitosamente'
                ]);
            }

            return redirect()->back()->with('success', 'Viaje reprogramado exitosamente');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in ViajeController@reprogramarViaje: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Get trip statistics and analytics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function estadisticas(Request $request)
    {
        try {
            $stats = [
                'total_viajes' => Viaje::count(),
                'viajes_completados' => Viaje::completados()->count(),
                'viajes_en_curso' => Viaje::enCurso()->count(),
                'viajes_programados' => Viaje::programados()->count(),
                'viajes_cancelados' => Viaje::cancelados()->count(),
                'viajes_retrasados' => Viaje::retrasados()->count(),
                'kilometros_totales' => Viaje::completados()
                    ->whereNotNull('kilometraje_final')
                    ->get()
                    ->sum(function ($viaje) {
                        return $viaje->kilometraje_final - $viaje->kilometraje_inicial;
                    }),
                'eficiencia_promedio' => Viaje::completados()
                    ->whereNotNull('kilometraje_final')
                    ->get()
                    ->average('eficiencia')
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);

        } catch (Exception $e) {
            Log::error('Error in ViajeController@estadisticas: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error retrieving statistics'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate comprehensive trip report using stored procedure
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generarReporte(Request $request)
    {
        // Validate request
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
        ], [
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria',
            'fecha_fin.required' => 'La fecha de fin es obligatoria',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio'
        ]);

        try {
            $fechaInicio = $request->fecha_inicio;
            $fechaFin = $request->fecha_fin;

            // Call stored procedure to generate comprehensive trip report
            $results = DB::select('CALL GenerarReporteViajes(?, ?)', [$fechaInicio, $fechaFin]);

            if (empty($results)) {
                throw new Exception('No se obtuvo respuesta del stored procedure');
            }

            // The stored procedure returns multiple result sets:
            // 1. Summary information
            // 2. Detailed trip information
            // 3. Statistics by status
            // 4. Statistics by truck
            // 5. Statistics by driver

            // Process results - Laravel returns all result sets as a flat array
            $processedResults = [];
            $currentSection = 'resumen';

            foreach ($results as $row) {
                // Check for section headers
                if (isset($row->tipo_reporte)) {
                    $currentSection = strtolower(str_replace('ESTADISTICAS_POR_', '', $row->tipo_reporte));
                    unset($row->tipo_reporte);
                    if (!isset($processedResults[$currentSection])) {
                        $processedResults[$currentSection] = [];
                    }
                    $processedResults[$currentSection][] = $row;
                } else {
                    // Main report data
                    if (!isset($processedResults[$currentSection])) {
                        $processedResults[$currentSection] = [];
                    }

                    // Parse JSON fields if they exist
                    if (isset($row->resumen_periodo)) {
                        $row->resumen_periodo = json_decode($row->resumen_periodo, true);
                    }

                    $processedResults[$currentSection][] = $row;
                }
            }

            // Extract summary information if available
            $resumenPeriodo = null;
            if (isset($processedResults['resumen'][0]->resumen_periodo)) {
                $resumenPeriodo = $processedResults['resumen'][0]->resumen_periodo;
            }

            Log::info('Trip report generated via stored procedure', [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'total_results' => count($results),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'periodo' => [
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin' => $fechaFin,
                        'dias_periodo' => Carbon::parse($fechaFin)->diffInDays(Carbon::parse($fechaInicio)) + 1
                    ],
                    'resumen_general' => $resumenPeriodo,
                    'viajes_detallados' => $processedResults['resumen'] ?? [],
                    'estadisticas_por_estado' => $processedResults['estado'] ?? [],
                    'estadisticas_por_camion' => $processedResults['camion'] ?? [],
                    'estadisticas_por_piloto' => $processedResults['piloto'] ?? [],
                    'total_secciones' => count($processedResults),
                    'fecha_generacion' => Carbon::now()->format('Y-m-d H:i:s')
                ],
                'message' => 'Reporte de viajes generado exitosamente'
            ]);

        } catch (Exception $e) {
            Log::error('Error in ViajeController@generarReporte: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate trip report in specific format (PDF, Excel, etc.)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportarReporte(Request $request)
    {
        // Validate request
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'formato' => 'required|in:json,csv,excel',
            'incluir_detalle' => 'boolean'
        ]);

        try {
            // Get report data using our stored procedure method
            $reportRequest = new Request([
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin
            ]);

            $reportResponse = $this->generarReporte($reportRequest);
            $reportData = json_decode($reportResponse->getContent(), true);

            if ($reportData['status'] !== 'success') {
                throw new Exception('Error generating base report data');
            }

            $data = $reportData['data'];
            $incluirDetalle = $request->get('incluir_detalle', true);

            switch ($request->formato) {
                case 'csv':
                    return $this->exportarCSV($data, $incluirDetalle);
                case 'excel':
                    return $this->exportarExcel($data, $incluirDetalle);
                case 'json':
                default:
                    return response()->json($reportData);
            }

        } catch (Exception $e) {
            Log::error('Error in ViajeController@exportarReporte: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Export report data to CSV format
     */
    private function exportarCSV($data, $incluirDetalle)
    {
        $filename = 'reporte_viajes_' . $data['periodo']['fecha_inicio'] . '_' . $data['periodo']['fecha_fin'] . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data, $incluirDetalle) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for proper Excel encoding
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header information
            fputcsv($file, ['REPORTE DE VIAJES']);
            fputcsv($file, ['Período:', $data['periodo']['fecha_inicio'] . ' al ' . $data['periodo']['fecha_fin']]);
            fputcsv($file, ['Generado:', $data['fecha_generacion']]);
            fputcsv($file, []);

            // Summary statistics
            if (isset($data['resumen_general'])) {
                fputcsv($file, ['RESUMEN GENERAL']);
                foreach ($data['resumen_general'] as $key => $value) {
                    fputcsv($file, [ucfirst(str_replace('_', ' ', $key)), $value]);
                }
                fputcsv($file, []);
            }

            // Detailed trips if requested
            if ($incluirDetalle && !empty($data['viajes_detallados'])) {
                fputcsv($file, ['DETALLE DE VIAJES']);
                fputcsv($file, [
                    'ID Viaje', 'Estado', 'Fecha', 'Camión', 'Piloto', 'Ruta',
                    'KM Inicial', 'KM Final', 'KM Recorridos', 'Horas Viaje', 'Evaluación'
                ]);

                foreach ($data['viajes_detallados'] as $viaje) {
                    fputcsv($file, [
                        $viaje->viaje_id ?? '',
                        $viaje->viaje_estado ?? '',
                        $viaje->fecha_viaje ?? '',
                        $viaje->camion_placa ?? '',
                        $viaje->piloto_nombre ?? '',
                        $viaje->ruta_descripcion ?? '',
                        $viaje->kilometraje_inicial ?? '',
                        $viaje->kilometraje_final ?? '',
                        $viaje->km_recorridos ?? '',
                        $viaje->horas_viaje ?? '',
                        $viaje->evaluacion_kilometraje ?? ''
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export report data to Excel format (simple CSV with Excel headers)
     */
    private function exportarExcel($data, $incluirDetalle)
    {
        // For now, return CSV with Excel-friendly headers
        // In a real implementation, you might use Laravel Excel package
        return $this->exportarCSV($data, $incluirDetalle);
    }
}
