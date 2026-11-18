<?php

namespace App\Http\Controllers;

use App\Models\Mesa;

class MesaController extends Controller
{
    /**
     * Tablero de mesas del mesero (CU-04).
     */
    public function index()
    {
        // Solo mesero o administrador
        if (session('rol') !== 'MESERO' && session('rol') !== 'ADMINISTRADOR') {
            return redirect()->route('inicio')->with('error', 'No tienes permiso para entrar a esta sección.');
        }

        $mesas = Mesa::where('activa', true)->orderBy('numero')->get();
        
        foreach ($mesas as $mesa) {
            // Una hora antes de la reserva, la mesa pasa a reservada.
            // Se guarda antes de agregar "listos" y "proxima", que no son columnas de la tabla.
            if ($mesa->estado === 'DISPONIBLE' && $mesa->reservasVigentes()->exists()) {
                $mesa->estado = 'RESERVADA';
                $mesa->save();
            }

            // Pedidos que cocina ya dejó listos para entregar
            $mesa->listos = $mesa->pedidos()->where('estado', 'LISTO')->whereNull('id_pago')->count();

            // Próxima reserva, para mostrarla en el menú de la mesa
            $mesa->proxima = $mesa->reservas()
                ->where('estado', 'ACTIVA')
                ->whereDate('fecha', '>=', today())
                ->orderBy('fecha')->orderBy('hora')
                ->first();
        }

        return view('mesas.index', compact('mesas'));
    }

    /**
     * Liberar una mesa: vuelve a disponible si no tiene pedidos sin entregar.
     * Si estaba reservada, se cancela la reserva vigente.
     */
    public function liberar(Mesa $mesa)
    {
        if (session('rol') !== 'MESERO' && session('rol') !== 'ADMINISTRADOR') {
            return redirect()->route('inicio')->with('error', 'No tienes permiso para entrar a esta sección.');
        }

        if ($mesa->pedidosPorCobrar()->exists()) {
            return redirect()->route('mesas.index')->with('error', "La mesa {$mesa->numero} tiene consumo sin cobrar. Cóbrala en caja antes de liberarla.");
        }

        if ($mesa->estado === 'RESERVADA') {
            $mesa->reservasVigentes()->update(['estado' => 'CANCELADA']);
        }

        $mesa->estado = 'DISPONIBLE';
        $mesa->save();

        return redirect()->route('mesas.index')->with('success', "La mesa {$mesa->numero} quedó disponible.");
    }
}