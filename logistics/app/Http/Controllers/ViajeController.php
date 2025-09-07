<?php

namespace App\Http\Controllers;

use App\Models\Viaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ViajeController extends Controller
{
    public function completarViaje(Viaje $viaje)
    {
        // 1. Actualizamos el estado del viaje en el modelo
        $viaje->estado = 'Completado';
        $viaje->kilometraje_final = request('kilometraje_final'); // Suponiendo que llega del formulario
        $viaje->fecha_fin = now();
        $viaje->save();

        // 2. Calculamos los kilómetros recorridos
        $kilometrosRecorridos = $viaje->kilometraje_final - $viaje->kilometraje_inicial;

        // 3. ¡Aquí llamamos al Stored Procedure! invocar o call
        if ($kilometrosRecorridos > 0) {
            DB::statement('CALL ActualizarKilometrajeCamion(?, ?)', [
                $viaje->camion_id,
                $kilometrosRecorridos
            ]);
        }

        // Redirigimos al usuario con un mensaje de éxito
        return redirect()->back()->with('success', '¡Viaje completado y kilometraje actualizado!');
    }
}
